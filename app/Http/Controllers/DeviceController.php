<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\TrackedObject;
use Illuminate\Http\Request;
use PhpMqtt\Client\Facades\MQTT;

class DeviceController extends Controller
{
    public function show(Device $device, \Illuminate\Http\Request $request)
    {
        $this->authorize($device);

        $period     = $request->query('period', 'today');
        $customDate = $request->query('date');
        $viewingAsId = $request->query('viewing_as');
        $viewingAs   = $viewingAsId ? \App\Models\User::find($viewingAsId) : null;
        $backUrl     = $viewingAs
            ? route('admin.users.dashboard', $viewingAs) . '?period=' . $period
            : route('user.index') . '?period=' . $period;

        [$since, $until] = $this->parsePeriod($period, $customDate);

        $periodLabels = [
            'today'  => 'Сьогодні',
            'week'   => 'Тиждень',
            'month'  => 'Місяць',
            '3month' => '3 місяці',
        ];
        if ($period === 'day' && $customDate) {
            $periodLabels['day'] = \Carbon\Carbon::parse($customDate)->format('d.m.Y');
        }

        $device->load('deviceActions.action', 'trackedObjects.company');

        // Linked tracked objects (via pivot)
        $linkedObjects = $device->trackedObjects;

        // Range pair partner (same name, opposite is_range_start)
        $rangePairPartner = null;
        $rawRangeStart    = $device->getRawOriginal('is_range_start');
        if (!$device->is_on_off && !is_null($rawRangeStart) && $device->company_id) {
            $rangePairPartner = Device::where('company_id', $device->company_id)
                ->where('name', $device->name)
                ->where('id', '!=', $device->id)
                ->whereNotNull('is_range_start')
                ->first();
        }

        // ── Range pair session stats ──────────────────────────────────────────
        $pairStats = null;
        if ($rangePairPartner) {
            $entryId = $device->is_range_start ? $device->id : $rangePairPartner->id;
            $exitId  = $device->is_range_start ? $rangePairPartner->id : $device->id;

            $pairLogs = DeviceLog::whereIn('device_id', [$entryId, $exitId])
                ->whereBetween('logged_at', [$since, $until])
                ->orderBy('logged_at')
                ->get();

            // Build sessions per unique object (data value)
            $objectIds = $pairLogs->pluck('data')->unique()->filter();
            $objects   = TrackedObject::where('company_id', $device->company_id)
                ->whereIn('external_id', $objectIds)->get()->keyBy('external_id');

            $perObject   = [];
            $totalMin    = 0;
            $totalSessions = 0;

            foreach ($objectIds as $extId) {
                $objLogs  = $pairLogs->filter(fn($l) => $l->data === $extId);
                $sessions = $this->buildSessions($objLogs, collect([$entryId]), collect([$exitId]), $until);

                $objMin  = $sessions->sum('duration_min');
                $totalMin      += $objMin;
                $totalSessions += $sessions->count();

                $perObject[] = [
                    'name'     => $objects->get($extId)?->name ?? $extId,
                    'object'   => $objects->get($extId),
                    'sessions' => $sessions->count(),
                    'total_h'  => floor($objMin / 60),
                    'total_m'  => $objMin % 60,
                    'open'     => $sessions->where('open', true)->count(),
                ];
            }

            $pairStats = [
                'per_object'    => $perObject,
                'total_sessions'=> $totalSessions,
                'total_h'       => floor($totalMin / 60),
                'total_m'       => $totalMin % 60,
            ];
        }

        // Last known state for ON/OFF devices
        $currentState = null;
        $lastStateLog = null;
        if ($device->is_on_off) {
            $lastStateLog = DeviceLog::where('device_id', $device->id)
                ->whereIn('data', ['on', 'off'])->latest('logged_at')->first();
            $currentState = $lastStateLog?->data ?? 'unknown';
        }

        // Last measurement for single-value devices
        $lastMeasurement = null;
        if (!$device->is_on_off && is_null($rawRangeStart)) {
            $lastMeasurement = DeviceLog::where('device_id', $device->id)
                ->latest('logged_at')->first();
        }

        // Resolve data → object name
        $objectMap = collect();
        if ($device->company_id) {
            $objectMap = TrackedObject::where('company_id', $device->company_id)
                ->pluck('name', 'external_id');
        }

        // Recent logs
        $recentLogs = DeviceLog::where('device_id', $device->id)
            ->with('action')->latest('logged_at')->take(50)->get();

        return view('user.devices.show', compact(
            'device', 'currentState', 'lastStateLog', 'lastMeasurement',
            'objectMap', 'recentLogs', 'backUrl', 'viewingAs',
            'linkedObjects', 'rangePairPartner', 'pairStats',
            'period', 'customDate', 'periodLabels', 'since', 'until'
        ));
    }

    private function parsePeriod(string $period, ?string $customDate): array
    {
        if ($period === 'day' && $customDate) {
            return [
                \Carbon\Carbon::parse($customDate)->startOfDay(),
                \Carbon\Carbon::parse($customDate)->endOfDay(),
            ];
        }
        $since = match($period) {
            'today'  => now()->startOfDay(),
            'week'   => now()->subWeek(),
            '3month' => now()->subMonths(3),
            default  => now()->subMonth(),
        };
        return [$since, now()];
    }

    private function buildSessions($logs, $entryIds, $exitIds, $until): \Illuminate\Support\Collection
    {
        $sessions  = [];
        $openEntry = null;

        foreach ($logs as $log) {
            if ($entryIds->contains($log->device_id) && $openEntry === null) {
                $openEntry = $log;
            } elseif ($exitIds->contains($log->device_id) && $openEntry !== null) {
                $entry = \Carbon\Carbon::parse($openEntry->logged_at);
                $exit  = \Carbon\Carbon::parse($log->logged_at);
                $sessions[] = [
                    'entry_at'     => $openEntry->logged_at,
                    'exit_at'      => $log->logged_at,
                    'duration_min' => (int) $entry->diffInMinutes($exit),
                    'open'         => false,
                ];
                $openEntry = null;
            }
        }
        if ($openEntry !== null) {
            $entry = \Carbon\Carbon::parse($openEntry->logged_at);
            $sessions[] = [
                'entry_at'     => $openEntry->logged_at,
                'exit_at'      => null,
                'duration_min' => (int) $entry->diffInMinutes($until),
                'open'         => true,
            ];
        }
        return collect($sessions);
    }

    public function sendCommand(Request $request, Device $device)
    {
        $this->authorize($device);

        $request->validate([
            'action_name' => 'nullable|string|max:50',
            'data'        => 'required|string|max:500',
        ]);

        $payload = json_encode([
            'id'   => $device->device_id,
            'act'  => $request->action_name ?: null,
            'data' => $request->data,
        ]);

        try {
            $mqtt = MQTT::connection();
            $mqtt->publish(config('mqtt.publish_topic', 'hashcontrol'), $payload, 0);
            $mqtt->disconnect();

            return back()->with('command_sent', true);
        } catch (\Exception $e) {
            return back()->with('command_error', $e->getMessage());
        }
    }

    private function authorize(Device $device): void
    {
        if (auth()->user()->role === 'admin') {
            return;
        }
        $user       = auth()->user();
        $companyIds = $user->companies()->pluck('companies.id');
        abort_unless(
            $device->user_id === $user->id || $companyIds->contains($device->company_id),
            403
        );
    }
}
