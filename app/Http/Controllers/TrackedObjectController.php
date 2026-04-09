<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\TrackedObject;
use Illuminate\Http\Request;
use PhpMqtt\Client\Facades\MQTT;

class TrackedObjectController extends Controller
{
    public function index()
    {
        $user       = auth()->user();
        $companyIds = $user->companies()->pluck('id');

        $objects = TrackedObject::whereIn('company_id', $companyIds)
            ->with('company')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('user.tracked-objects.index', compact('objects'));
    }

    public function create()
    {
        $companies = auth()->user()->companies;
        return view('user.tracked-objects.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $user       = auth()->user();
        $companyIds = $user->companies()->pluck('id')->toArray();

        $request->validate([
            'external_id' => 'required|string|max:100',
            'company_id'  => 'required|in:' . implode(',', $companyIds),
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:shop,generator,fridge,counter,worker,thermometer,other',
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

        return redirect()->route('user.tracked-objects.index')->with('success', 'Об\'єкт зареєстровано.');
    }

    public function show(TrackedObject $trackedObject, Request $request)
    {
        $this->authorizeObject($trackedObject);

        // ── Period / date ─────────────────────────────────────────────────────
        $period     = $request->query('period', 'month');
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
        $stats = [
            'day'   => DeviceLog::where('data', $trackedObject->external_id)->where('logged_at', '>=', now()->subDay())->count(),
            'week'  => DeviceLog::where('data', $trackedObject->external_id)->where('logged_at', '>=', now()->subWeek())->count(),
            'month' => DeviceLog::where('data', $trackedObject->external_id)->where('logged_at', '>=', now()->subMonth())->count(),
        ];

        $recentLogs = $periodLogs->sortByDesc('logged_at');

        return view('user.tracked-objects.show', compact(
            'trackedObject', 'associatedDevices', 'availableDevices',
            'period', 'customDate', 'since', 'until',
            'periodLogs', 'sessions', 'hasRangePair', 'periodSummary',
            'stats', 'recentLogs'
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
            if ($entryDeviceIds->contains($log->device_id)) {
                // New entry — if a previous session was left open without exit, close it implicitly
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
        $companies = auth()->user()->companies;
        return view('user.tracked-objects.create', compact('trackedObject', 'companies'));
    }

    public function update(Request $request, TrackedObject $trackedObject)
    {
        $this->authorizeObject($trackedObject);

        $companyIds = auth()->user()->companies()->pluck('id')->toArray();

        $request->validate([
            'external_id' => 'required|string|max:100',
            'company_id'  => 'required|in:' . implode(',', $companyIds),
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:shop,generator,fridge,counter,worker,thermometer,other',
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
            $companyIds = auth()->user()->companies()->pluck('id');
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
            $companyIds = auth()->user()->companies()->pluck('id');
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

            return back()->with('command_sent', "Команду відправлено: {$payload}");
        } catch (\Exception $e) {
            return back()->with('command_error', 'Помилка відправки MQTT: ' . $e->getMessage());
        }
    }

    private function authorizeObject(TrackedObject $obj): void
    {
        if (auth()->user()->role === 'admin') {
            return;
        }
        $companyIds = auth()->user()->companies()->pluck('id');
        abort_unless($companyIds->contains($obj->company_id), 403);
    }
}
