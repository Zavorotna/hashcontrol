<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\TrackedObject;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpMqtt\Client\Facades\MQTT;

class TrackedObjectController extends Controller
{
    private function getCompanyIds(int $userId): \Illuminate\Support\Collection
    {
        $pivotIds  = DB::table('company_user')->where('user_id', $userId)->pluck('company_id');
        $legacyIds = DB::table('companies')->where('user_id', $userId)->pluck('id');
        return $pivotIds->merge($legacyIds)->unique()->values();
    }

    public function index()
    {
        $user  = auth()->user();
        $query = TrackedObject::with('company')->orderBy('type')->orderBy('name');

        if ($user->role !== 'admin') {
            $companyIds = $this->getCompanyIds($user->id);
            $query->whereIn('company_id', $companyIds);
        }

        $objects = $query->get();

        return view('user.tracked-objects.index', compact('objects'));
    }

    public function create()
    {
        $user      = auth()->user();
        $companies = $user->role === 'admin'
            ? Company::orderBy('name')->get()
            : Company::whereIn('id', $this->getCompanyIds($user->id))->get();
        $existingTypes = TrackedObject::whereIn('company_id', $companies->pluck('id'))
            ->distinct()->orderBy('type')->pluck('type');
        return view('user.tracked-objects.create', compact('companies', 'existingTypes'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $companyRule = 'required|exists:companies,id';
        } else {
            $companyIds  = $this->getCompanyIds($user->id)->toArray();
            $companyRule = 'required|in:' . implode(',', $companyIds ?: [0]);
        }

        $request->validate([
            'external_id' => 'required|string|max:100',
            'company_id'  => $companyRule,
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|max:50',
            'tenant_name' => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'notes'       => 'nullable|string',
        ]);

        $exists = TrackedObject::where('external_id', $request->external_id)
            ->where('company_id', $request->company_id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['external_id' => 'Цей ID вже зареєстровано для даної компанії.'])->withInput();
        }

        TrackedObject::create($request->only(
            'external_id', 'company_id', 'name', 'type',
            'tenant_name', 'email', 'phone', 'address', 'notes'
        ));

        $redirect = auth()->user()->role === 'admin'
            ? redirect()->route('user.tracked-objects.index')
            : redirect()->route('user.companies');

        return $redirect->with('success', 'Об\'єкт зареєстровано.');
    }

    public function show(TrackedObject $trackedObject, Request $request)
    {
        $this->authorizeObject($trackedObject);

        // ── Period / date ─────────────────────────────────────────────────────
        $period     = $request->query('period', 'today');
        $customDate = $request->query('date');

        if ($period === 'day' && $customDate) {
            $since = \Carbon\Carbon::parse($customDate)->startOfDay();
            $until = \Carbon\Carbon::parse($customDate)->endOfDay();
        } else {
            $since = match($period) {
                'today'  => now()->startOfDay(),
                'week'   => now()->subWeek(),
                '3month' => now()->subMonths(3),
                default  => now()->subMonth(),
            };
            $until = now();
        }

        // ── Devices explicitly linked to this object (pivot) ─────────────────
        $trackedObject->load('devices');

        // ── Devices that have logged data for this object ─────────────────────
        $loggedDeviceIds = DeviceLog::where('data', $trackedObject->external_id)
            ->pluck('device_id')->unique();

        $associatedDevices = Device::whereIn('id', $loggedDeviceIds)
            ->with('deviceActions.action')
            ->get();

        // ── All devices of the object's company, excluding already attached ─────
        $attachedIds = $trackedObject->devices->pluck('id');
        $availableDevices = Device::where('company_id', $trackedObject->company_id)
            ->whereNotIn('id', $attachedIds)
            ->orderByDesc('created_at')
            ->get();

        // Entry devices (is_range_start = true) and exit devices (is_range_start = false).
        // DB-level WHERE correctly excludes NULL values, so measurement devices are ignored.
        $entryDeviceIds = Device::whereIn('id', $loggedDeviceIds)->where('is_range_start', true)->pluck('id');
        $exitDeviceIds  = Device::whereIn('id', $loggedDeviceIds)->where('is_range_start', false)->pluck('id');
        $hasRangePair   = $entryDeviceIds->isNotEmpty() && $exitDeviceIds->isNotEmpty();

        // ── Current status ────────────────────────────────────────────────────
        $currentStatus = null;
        if ($hasRangePair) {
            $lastEntry = DeviceLog::where('data', $trackedObject->external_id)->whereIn('device_id', $entryDeviceIds)->latest('logged_at')->first();
            $lastExit  = DeviceLog::where('data', $trackedObject->external_id)->whereIn('device_id', $exitDeviceIds)->latest('logged_at')->first();

            if ($lastEntry) {
                $isInside  = !$lastExit || $lastEntry->logged_at > $lastExit->logged_at;
                $sinceTime = $isInside ? $lastEntry->logged_at : $lastExit->logged_at;
                $diffMin   = (int) now()->diffInMinutes(\Carbon\Carbon::parse($sinceTime));
                $currentStatus = [
                    'inside'    => $isInside,
                    'since'     => $sinceTime,
                    'diff_min'  => $diffMin,
                ];
            }
        }

        // ── Logs in selected period ───────────────────────────────────────────
        $periodLogs = DeviceLog::where('data', $trackedObject->external_id)
            ->whereBetween('logged_at', [$since, $until])
            ->with(['device', 'action'])
            ->orderBy('logged_at')
            ->get();

        // ── Build entry/exit sessions ─────────────────────────────────────────
        $sessions = collect();
        if ($hasRangePair) {
            $sessions = $this->buildSessions($periodLogs, $entryDeviceIds, $exitDeviceIds, $until);
        }

        // ── Period summary ────────────────────────────────────────────────────
        if ($hasRangePair) {
            $totalMin = $sessions->sum('duration_min');
            $periodSummary = [
                'sessions'  => $sessions->count(),
                'total_h'   => floor($totalMin / 60),
                'total_m'   => $totalMin % 60,
                'avg_min'   => $sessions->count() > 0 ? (int) round($totalMin / $sessions->count()) : 0,
            ];
        } else {
            $periodSummary = ['accesses' => $periodLogs->count()];
        }

        // ── All-time quick stats (sidebar counters) ───────────────────────────
        // Count only entry-device logs when a range pair exists — each entry = 1 session.
        // Without a pair every log is an independent access, so no filter is needed.
        $statsBase = DeviceLog::where('data', $trackedObject->external_id)
            ->when($hasRangePair, fn($q) => $q->whereIn('device_id', $entryDeviceIds));

        $stats = [
            'day'   => (clone $statsBase)->where('logged_at', '>=', now()->subDay())->count(),
            'week'  => (clone $statsBase)->where('logged_at', '>=', now()->subWeek())->count(),
            'month' => (clone $statsBase)->where('logged_at', '>=', now()->subMonth())->count(),
        ];

        // ── ON/OFF cross-stats for linked devices ─────────────────────────────
        $onOffCrossStats = [];
        foreach ($trackedObject->devices->where('is_on_off', true) as $onOffDevice) {
            $onOffLogs = DeviceLog::where('device_id', $onOffDevice->id)
                ->whereIn('data', ['on', 'off'])
                ->whereBetween('logged_at', [$since, $until])
                ->orderBy('logged_at')->get();

            $lastBefore = DeviceLog::where('device_id', $onOffDevice->id)
                ->whereIn('data', ['on', 'off'])
                ->where('logged_at', '<', $since)
                ->latest('logged_at')->first();

            $intervals      = [];
            $onAt           = ($lastBefore && $lastBefore->data === 'on') ? $since->copy() : null;
            $totalOnSeconds = 0;

            foreach ($onOffLogs as $log) {
                if ($log->data === 'on' && $onAt === null) {
                    $onAt = Carbon::parse($log->logged_at);
                } elseif ($log->data === 'off' && $onAt !== null) {
                    $end            = Carbon::parse($log->logged_at);
                    $intervals[]    = [$onAt, $end];
                    $totalOnSeconds += $onAt->diffInSeconds($end);
                    $onAt           = null;
                }
            }
            if ($onAt !== null) {
                $intervals[]    = [$onAt, $until->copy()];
                $totalOnSeconds += $onAt->diffInSeconds($until);
            }

            $lastStateLog = DeviceLog::where('device_id', $onOffDevice->id)
                ->whereIn('data', ['on', 'off'])->latest('logged_at')->first();

            $duringOn = 0;
            foreach ($intervals as [$iStart, $iEnd]) {
                $duringOn += DeviceLog::where('data', $trackedObject->external_id)
                    ->whereBetween('logged_at', [$iStart, $iEnd])->count();
            }

            $overlapMinutes = null;
            if ($hasRangePair && !empty($intervals)) {
                $objLogs = DeviceLog::where('data', $trackedObject->external_id)
                    ->whereBetween('logged_at', [$since, $until])
                    ->whereIn('device_id', $entryDeviceIds->merge($exitDeviceIds))
                    ->orderBy('logged_at')->get();
                $objSessions    = $this->buildSimpleSessions($objLogs, $entryDeviceIds, $exitDeviceIds, $until);
                $overlapSeconds = $this->intersectIntervals($objSessions, $intervals);
                $overlapMinutes = (int) round($overlapSeconds / 60);
            }

            $periodSeconds = $since->diffInSeconds($until);
            $onOffCrossStats[] = [
                'device'          => $onOffDevice,
                'current_state'   => $lastStateLog?->data ?? 'unknown',
                'on_h'            => floor($totalOnSeconds / 3600),
                'on_m'            => floor(($totalOnSeconds % 3600) / 60),
                'on_percent'      => $periodSeconds > 0 && $totalOnSeconds > 0
                    ? round($totalOnSeconds / $periodSeconds * 100) : 0,
                'during_on'       => $duringOn,
                'overlap_minutes' => $overlapMinutes,
                'has_intervals'   => !empty($intervals),
            ];
        }

        $recentLogs = $periodLogs->sortByDesc('logged_at');

        return view('user.tracked-objects.show', compact(
            'trackedObject', 'associatedDevices', 'availableDevices',
            'period', 'customDate', 'since', 'until',
            'periodLogs', 'sessions', 'hasRangePair', 'periodSummary',
            'stats', 'recentLogs', 'currentStatus', 'onOffCrossStats'
        ));
    }

    /**
     * Pair entry/exit logs into sessions with duration.
     * If a session has no matching exit within the period, it is marked as still open.
     */
    private function buildSessions($logs, $entryDeviceIds, $exitDeviceIds, $until): \Illuminate\Support\Collection
    {
        $sessions  = [];
        $openEntry = null;

        foreach ($logs as $log) {
            if ($entryDeviceIds->contains($log->device_id) && $openEntry === null) {
                // Entry only if not already inside (no duplicate entries without exit)
                $openEntry = $log;
            } elseif ($exitDeviceIds->contains($log->device_id) && $openEntry !== null) {
                $entryTime = \Carbon\Carbon::parse($openEntry->logged_at);
                $exitTime  = \Carbon\Carbon::parse($log->logged_at);

                $sessions[] = [
                    'entry_at'     => $openEntry->logged_at,
                    'exit_at'      => $log->logged_at,
                    'duration_min' => (int) $entryTime->diffInMinutes($exitTime),
                    'entry_device' => $openEntry->device,
                    'exit_device'  => $log->device,
                    'open'         => false,
                ];
                $openEntry = null;
            }
        }

        // Session still open at the end of the period
        if ($openEntry !== null) {
            $entryTime = \Carbon\Carbon::parse($openEntry->logged_at);
            $sessions[] = [
                'entry_at'     => $openEntry->logged_at,
                'exit_at'      => null,
                'duration_min' => (int) $entryTime->diffInMinutes($until),
                'entry_device' => $openEntry->device,
                'exit_device'  => null,
                'open'         => true,
            ];
        }

        return collect($sessions);
    }

    public function edit(TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);
        $user      = auth()->user();
        $companies = $user->role === 'admin'
            ? Company::orderBy('name')->get()
            : Company::whereIn('id', $this->getCompanyIds($user->id))->get();
        $existingTypes = TrackedObject::whereIn('company_id', $companies->pluck('id'))
            ->distinct()->orderBy('type')->pluck('type');
        return view('user.tracked-objects.create', compact('trackedObject', 'companies', 'existingTypes'));
    }

    public function update(Request $request, TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);

        $user = auth()->user();
        if ($user->role === 'admin') {
            $companyRule = 'required|exists:companies,id';
        } else {
            $companyIds  = $this->getCompanyIds($user->id)->toArray();
            $companyRule = 'required|in:' . implode(',', $companyIds ?: [0]);
        }

        $request->validate([
            'external_id' => 'required|string|max:100',
            'company_id'  => $companyRule,
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|max:50',
            'tenant_name' => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'notes'       => 'nullable|string',
        ]);

        $trackedObject->update($request->only(
            'external_id', 'company_id', 'name', 'type',
            'tenant_name', 'email', 'phone', 'address', 'notes'
        ));

        return redirect()->route('user.tracked-objects.show', $trackedObject)->with('success', 'Збережено.');
    }

    public function destroy(TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);
        $trackedObject->delete();
        return redirect()->route('user.tracked-objects.index')->with('success', 'Об\'єкт видалено.');
    }

    public function attachDevice(Request $request, TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);

        $request->validate(['device_id' => 'required|exists:devices,id']);

        $device = Device::findOrFail($request->device_id);

        // Ensure device belongs to user's company (admin bypasses)
        if (auth()->user()->role !== 'admin') {
            $companyIds = $this->getCompanyIds(auth()->id());
            abort_unless($companyIds->contains($device->company_id), 403);
        }

        $trackedObject->devices()->syncWithoutDetaching([$device->id]);

        return back()->with('success', "Пристрій «{$device->name}» прив'язано.");
    }

    public function detachDevice(TrackedObject $trackedObject, Device $device)
    {
        $this->authorizeObject($trackedObject);

        $trackedObject->devices()->detach($device->id);

        return back()->with('success', "Пристрій «{$device->name}» відв'язано.");
    }

    /**
     * Send a command to a device via MQTT.
     * POST /user/tracked-objects/{trackedObject}/send-command
     */
    public function sendCommand(Request $request, TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);

        $request->validate([
            'device_id'   => 'required|exists:devices,id',
            'action_name' => 'required|string|max:100',
            'data'        => 'required|string|max:500',
        ]);

        $device = Device::findOrFail($request->device_id);

        // Verify that the device belongs to the user's company (admin bypasses)
        if (auth()->user()->role !== 'admin') {
            $companyIds = $this->getCompanyIds(auth()->id());
            abort_unless($companyIds->contains($device->company_id), 403);
        }

        $payload = json_encode([
            'id'   => $device->device_id,
            'act'  => $request->action_name,
            'data' => $request->data,
        ]);

        try {
            $mqtt = MQTT::connection();
            $mqtt->publish(config('mqtt.publish_topic', 'hashcontrol'), $payload, 0);
            $mqtt->disconnect();

            return back()->with('command_sent', true);
        } catch (\Exception $e) {
            return back()->with('command_error', 'Помилка відправки MQTT: ' . $e->getMessage());
        }
    }

    private function authorizeObject(TrackedObject $obj): void
    {
        if (auth()->user()->role === 'admin') {
            return;
        }
        $companyIds = $this->getCompanyIds(auth()->id());
        abort_unless($companyIds->contains($obj->company_id), 403);
    }

    private function buildSimpleSessions($logs, $entryIds, $exitIds, Carbon $until): array
    {
        $sessions  = [];
        $openEntry = null;
        foreach ($logs as $log) {
            if ($entryIds->contains($log->device_id) && $openEntry === null) {
                $openEntry = Carbon::parse($log->logged_at);
            } elseif ($exitIds->contains($log->device_id) && $openEntry !== null) {
                $sessions[] = [$openEntry, Carbon::parse($log->logged_at)];
                $openEntry  = null;
            }
        }
        if ($openEntry !== null) {
            $sessions[] = [$openEntry, $until->copy()];
        }
        return $sessions;
    }

    private function intersectIntervals(array $sessions, array $onIntervals): int
    {
        $total = 0;
        foreach ($sessions as [$sStart, $sEnd]) {
            foreach ($onIntervals as [$oStart, $oEnd]) {
                $start = max($sStart->timestamp, $oStart->timestamp);
                $end   = min($sEnd->timestamp, $oEnd->timestamp);
                if ($end > $start) {
                    $total += $end - $start;
                }
            }
        }
        return $total;
    }
}
