<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\TrackedObject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $period      = $request->query('period', 'week');
        $date        = $request->query('date');
        $deviceView  = $request->query('device_view', 'my'); // 'my' or 'all'
        $data        = $this->getDashboardData(auth()->user(), $period, $date, $deviceView);
        return view('user.index', array_merge($data, ['deviceView' => $deviceView]));
    }

    public function settings()
    {
        return view('user.settings');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Введіть поточний пароль.',
            'password.required'         => 'Введіть новий пароль.',
            'password.min'              => 'Пароль має бути не менше 6 символів.',
            'password.confirmed'        => 'Паролі не співпадають.',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Поточний пароль невірний.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Пароль успішно змінено.');
    }

    public function getDashboardData(User $user, string $period = 'week', ?string $customDate = null, string $deviceView = 'my'): array
    {
        if ($period === 'day' && $customDate) {
            $since = Carbon::parse($customDate)->startOfDay();
            $until = Carbon::parse($customDate)->endOfDay();
        } else {
            $since = match($period) {
                'today'  => now()->startOfDay(),
                'month'  => now()->subMonth(),
                '3month' => now()->subMonths(3),
                default  => now()->subWeek(),
            };
            $until = now();
        }

        $companies  = $user->companies()->with('offices')->get();
        $companyIds = $companies->pluck('id');

        if ($deviceView === 'all') {
            // All devices linked to tracked objects of user's companies
            $objectDeviceIds = \App\Models\TrackedObject::whereIn('company_id', $companyIds)
                ->with('devices')
                ->get()
                ->flatMap(fn($obj) => $obj->devices->pluck('id'))
                ->unique();
            $devices = \App\Models\Device::whereIn('id', $objectDeviceIds)
                ->with('deviceActions.action')
                ->orderByDesc('created_at')
                ->get();
        } else {
            // My devices: directly assigned to the user
            $devices = $user->devices()->with('deviceActions.action')->orderByDesc('created_at')->get();
        }

        $deviceIds  = $devices->pluck('id');

        $allObjects = TrackedObject::whereIn('company_id', $companyIds)->get();

        // Only reader devices (is_range_start IS NOT NULL, is_on_off = false) log
        // object external_ids as their data value.
        // ON/OFF devices send 'on'/'off'; measurement devices (thermometers, counters)
        // send sensor readings — neither should appear as unregistered objects.
        $readerDeviceIds = Device::whereIn('id', $deviceIds)
            ->where('is_on_off', false)
            ->whereNotNull('is_range_start')
            ->pluck('id');

        $allDataIds = DeviceLog::whereIn('device_id', $readerDeviceIds)
            ->select('data')->distinct()->orderBy('data')->pluck('data');

        $unregisteredDataIds = $allDataIds->diff($allObjects->pluck('external_id'))->values();

        // Per-object stats for selected period
        $objectStats = [];
        foreach ($allObjects as $obj) {
            $lastLog = DeviceLog::where('data', $obj->external_id)
                ->with('action')
                ->latest('logged_at')
                ->first();

            $objectStats[$obj->id] = [
                'period'      => DeviceLog::where('data', $obj->external_id)->whereBetween('logged_at', [$since, $until])->count(),
                'day'         => DeviceLog::where('data', $obj->external_id)->where('logged_at', '>=', now()->subDay())->count(),
                'week'        => DeviceLog::where('data', $obj->external_id)->where('logged_at', '>=', now()->subWeek())->count(),
                'month'       => DeviceLog::where('data', $obj->external_id)->where('logged_at', '>=', now()->subMonth())->count(),
                'last_data'   => $lastLog?->data,
                'last_action' => $lastLog?->action?->title ?? $lastLog?->action?->name,
                'last_at'     => $lastLog?->logged_at,
            ];
        }

        $objectsByCompany = $allObjects->groupBy('company_id');

        // Per-device stats for selected period
        $deviceStats = [];
        foreach ($devices as $device) {
            $count = DeviceLog::where('device_id', $device->id)
                ->whereBetween('logged_at', [$since, $until])
                ->count();

            $lastLog = DeviceLog::where('device_id', $device->id)
                ->latest('logged_at')
                ->first();

            $deviceStats[$device->id] = [
                'period_count' => $count,
                'last_at'      => $lastLog?->logged_at,
                'last_data'    => $lastLog?->data,
            ];
        }

        // ON/OFF cross-stats
        $onOffStats = [];
        $onOffDevices = $devices->where('is_on_off', true);

        foreach ($onOffDevices as $device) {
            $logs = DeviceLog::where('device_id', $device->id)
                ->whereIn('data', ['on', 'off'])
                ->whereBetween('logged_at', [$since, $until])
                ->orderBy('logged_at')
                ->get();

            // Check if device was ON before the period start
            $lastBefore = DeviceLog::where('device_id', $device->id)
                ->whereIn('data', ['on', 'off'])
                ->where('logged_at', '<', $since)
                ->latest('logged_at')
                ->first();

            $intervals      = [];
            $onAt           = ($lastBefore && $lastBefore->data === 'on') ? $since : null;
            $totalOnSeconds = 0;

            foreach ($logs as $log) {
                if ($log->data === 'on' && $onAt === null) {
                    $onAt = $log->logged_at;
                } elseif ($log->data === 'off' && $onAt !== null) {
                    $intervals[]     = [$onAt, $log->logged_at];
                    $totalOnSeconds += Carbon::parse($onAt)->diffInSeconds($log->logged_at);
                    $onAt            = null;
                }
            }

            // Device is currently ON (open interval — close it at period end)
            if ($onAt !== null) {
                $intervals[]     = [$onAt, $until];
                $totalOnSeconds += Carbon::parse($onAt)->diffInSeconds($until);
            }

            $periodSeconds = $since->diffInSeconds($until);
            $onPercent     = $periodSeconds > 0 ? round($totalOnSeconds / $periodSeconds * 100) : 0;

            // Last known state
            $lastStateLog = DeviceLog::where('device_id', $device->id)
                ->whereIn('data', ['on', 'off'])
                ->latest('logged_at')
                ->first();
            $currentState = $lastStateLog?->data ?? 'unknown';

            // Cross-stats: events per tracked object during ON intervals
            $crossStats = [];
            if ($device->company_id) {
                $companyObjects = $allObjects->where('company_id', $device->company_id);

                foreach ($companyObjects as $obj) {
                    $duringOn = 0;
                    foreach ($intervals as [$start, $end]) {
                        $duringOn += DeviceLog::where('data', $obj->external_id)
                            ->whereBetween('logged_at', [$start, $end])
                            ->count();
                    }

                    $total = $objectStats[$obj->id]['period'] ?? 0;

                    $crossStats[] = [
                        'object'    => $obj,
                        'during_on' => $duringOn,
                        'total'     => $total,
                        'percent'   => $total > 0 ? round($duringOn / $total * 100) : null,
                    ];
                }
            }

            $onOffStats[] = [
                'device'        => $device,
                'current_state' => $currentState,
                'total_on_sec'  => $totalOnSeconds,
                'on_percent'    => $onPercent,
                'cross_stats'   => $crossStats,
            ];
        }

        $logs = DeviceLog::whereIn('device_id', $deviceIds)
            ->with(['device', 'action'])
            ->whereBetween('logged_at', [$since, $until])
            ->latest('logged_at')
            ->take(50)
            ->get()
            ->map(function ($log) use ($allObjects) {
                $log->tracked_object = $allObjects->firstWhere('external_id', $log->data);
                return $log;
            });

        return compact(
            'companies', 'devices', 'logs', 'period', 'since', 'until', 'customDate',
            'objectsByCompany', 'allObjects',
            'objectStats', 'deviceStats', 'onOffStats',
            'unregisteredDataIds', 'allDataIds',
        );
    }
}
