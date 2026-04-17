<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\TrackedObject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // ── Page controllers ─────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $period = $request->query('period', 'week');
        $date   = $request->query('date');
        $data   = $this->getDevicesPageData(auth()->user(), $period, $date, 'all');
        return view('user.index', $data);
    }

    public function companies(Request $request)
    {
        $period = $request->query('period', 'week');
        $date   = $request->query('date');
        $data   = $this->getCompaniesPageData(auth()->user(), $period, $date);
        return view('user.companies', $data);
    }

    public function events(Request $request)
    {
        $period = $request->query('period', 'week');
        $date   = $request->query('date');
        $data   = $this->getEventsPageData(auth()->user(), $period, $date);
        return view('user.events', $data);
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
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Поточний пароль невірний.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Пароль успішно змінено.');
    }

    // ── Get company IDs for a user (pivot + legacy user_id column) ───────────

    private function getCompanyIds(User $user): \Illuminate\Support\Collection
    {
        $pivotIds  = DB::table('company_user')->where('user_id', $user->id)->pluck('company_id');
        $legacyIds = DB::table('companies')->where('user_id', $user->id)->pluck('id');
        return $pivotIds->merge($legacyIds)->unique()->values();
    }

    // ── Period helper ─────────────────────────────────────────────────────────

    private function parsePeriod(string $period, ?string $customDate): array
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
        return [$since, $until];
    }

    // Whether the period exceeds 30 days (needs archive)
    private function needsArchive(Carbon $since): bool
    {
        return $since->lt(now()->subDays(30));
    }

    // Query builder that unions device_logs + archive when needed
    private function logQuery(Carbon $since): \Illuminate\Database\Query\Builder
    {
        $main = DB::table('device_logs');

        if ($this->needsArchive($since)) {
            $archive = DB::table('device_logs_archive');
            return DB::query()->fromSub(
                $main->union($archive),
                'dlogs'
            );
        }

        return $main;
    }

    // ── Devices page data ─────────────────────────────────────────────────────

    public function getDevicesPageData(User $user, string $period = 'week', ?string $customDate = null, string $deviceView = 'my'): array
    {
        [$since, $until] = $this->parsePeriod($period, $customDate);

        $companyIds = $this->getCompanyIds($user);
        $companies  = \App\Models\Company::whereIn('id', $companyIds)->get();

        $devices = Device::where(function ($q) use ($user, $companyIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('company_id', $companyIds);
            })
            ->with('deviceActions.action')
            ->orderByDesc('created_at')
            ->get();

        $deviceIds = $devices->pluck('id');

        // Per-device stats
        $deviceStats = [];
        foreach ($devices as $device) {
            $lastLog = DeviceLog::where('device_id', $device->id)->latest('logged_at')->first();
            $deviceStats[$device->id] = [
                'period_count' => $this->logQuery($since)
                    ->where('device_id', $device->id)
                    ->whereBetween('logged_at', [$since, $until])
                    ->count(),
                'last_at'   => $lastLog?->logged_at,
                'last_data' => $lastLog?->data,
            ];
        }

        // ON/OFF stats
        $onOffStats   = $this->buildOnOffStats($devices, $since, $until, $companyIds);

        $periodLabels = $this->periodLabels($period, $customDate);

        return compact('devices', 'deviceStats', 'onOffStats', 'period', 'customDate', 'since', 'until', 'periodLabels');
    }

    // ── Companies page data ───────────────────────────────────────────────────

    public function getCompaniesPageData(User $user, string $period = 'week', ?string $customDate = null): array
    {
        [$since, $until] = $this->parsePeriod($period, $customDate);

        $companyIds = $this->getCompanyIds($user);
        $companies  = \App\Models\Company::with('offices')->whereIn('id', $companyIds)->get();
        $allObjects = TrackedObject::whereIn('company_id', $companyIds)->get();

        // Unregistered data IDs (from reader devices)
        $readerDeviceIds = Device::where('user_id', $user->id)
            ->where('is_on_off', false)->whereNotNull('is_range_start')->pluck('id');

        $allDataIds = $this->logQuery($since)
            ->whereIn('device_id', $readerDeviceIds)
            ->distinct()->orderBy('data')->pluck('data');

        $unregisteredDataIds = $allDataIds->diff($allObjects->pluck('external_id'))->values();

        // Object stats
        $objectStats = [];
        foreach ($allObjects as $obj) {
            $lastLog = DeviceLog::where('data', $obj->external_id)->latest('logged_at')->first();
            $objectStats[$obj->id] = [
                'period'      => $this->logQuery($since)->where('data', $obj->external_id)->whereBetween('logged_at', [$since, $until])->count(),
                'last_data'   => $lastLog?->data,
                'last_action' => null, // loaded separately if needed
                'last_at'     => $lastLog?->logged_at,
            ];
        }

        $objectsByCompany = $allObjects->groupBy('company_id');
        $periodLabels     = $this->periodLabels($period, $customDate);

        return compact('companies', 'allObjects', 'objectsByCompany', 'objectStats',
            'unregisteredDataIds', 'period', 'customDate', 'since', 'until', 'periodLabels');
    }

    // ── Events page data ──────────────────────────────────────────────────────

    public function getEventsPageData(User $user, string $period = 'week', ?string $customDate = null): array
    {
        [$since, $until] = $this->parsePeriod($period, $customDate);

        $companyIds = $this->getCompanyIds($user);
        $allObjects = TrackedObject::whereIn('company_id', $companyIds)->get();
        $deviceIds  = Device::where(function ($q) use ($user, $companyIds) {
            $q->where('user_id', $user->id)->orWhereIn('company_id', $companyIds);
        })->pluck('id');

        $logs = DeviceLog::whereIn('device_id', $deviceIds)
            ->with(['device', 'action'])
            ->whereBetween('logged_at', [$since, $until])
            ->latest('logged_at')
            ->paginate(100)
            ->through(function ($log) use ($allObjects) {
                $log->tracked_object = $allObjects->firstWhere('external_id', $log->data);
                return $log;
            });

        $periodLabels = $this->periodLabels($period, $customDate);

        return compact('logs', 'period', 'customDate', 'since', 'until', 'periodLabels');
    }

    // ── ON/OFF stats builder ──────────────────────────────────────────────────

    private function buildOnOffStats($devices, Carbon $since, Carbon $until, $companyIds): array
    {
        $onOffStats   = [];
        $onOffDevices = $devices->where('is_on_off', true);
        $allObjects   = TrackedObject::whereIn('company_id', $companyIds)->get();

        foreach ($onOffDevices as $device) {
            $logs = DeviceLog::where('device_id', $device->id)
                ->whereIn('data', ['on', 'off'])
                ->whereBetween('logged_at', [$since, $until])
                ->orderBy('logged_at')->get();

            $lastBefore = DeviceLog::where('device_id', $device->id)
                ->whereIn('data', ['on', 'off'])
                ->where('logged_at', '<', $since)
                ->latest('logged_at')->first();

            $intervals      = [];
            $onAt           = ($lastBefore && $lastBefore->data === 'on') ? $since : null;
            $totalOnSeconds = 0;

            foreach ($logs as $log) {
                if ($log->data === 'on' && $onAt === null) {
                    $onAt = $log->logged_at;
                } elseif ($log->data === 'off' && $onAt !== null) {
                    $intervals[]     = [Carbon::parse($onAt), Carbon::parse($log->logged_at)];
                    $totalOnSeconds += Carbon::parse($onAt)->diffInSeconds($log->logged_at);
                    $onAt            = null;
                }
            }
            if ($onAt !== null) {
                $intervals[]     = [Carbon::parse($onAt), $until->copy()];
                $totalOnSeconds += Carbon::parse($onAt)->diffInSeconds($until);
            }

            // Skip if device never turned on during this period
            if (empty($intervals)) {
                continue;
            }

            $periodSeconds = $since->diffInSeconds($until);
            $onPercent     = $periodSeconds > 0 ? round($totalOnSeconds / $periodSeconds * 100) : 0;

            $lastStateLog = DeviceLog::where('device_id', $device->id)
                ->whereIn('data', ['on', 'off'])->latest('logged_at')->first();
            $currentState = $lastStateLog?->data ?? 'unknown';

            // Cross-stats with intersection time
            $crossStats = [];
            if ($device->company_id) {
                $companyObjects = $allObjects->where('company_id', $device->company_id);

                // Get range-pair device IDs for this company
                $entryIds = Device::where('company_id', $device->company_id)->where('is_range_start', true)->pluck('id');
                $exitIds  = Device::where('company_id', $device->company_id)->where('is_range_start', false)->pluck('id');
                $hasPair  = $entryIds->isNotEmpty() && $exitIds->isNotEmpty();

                foreach ($companyObjects as $obj) {
                    $duringOn = 0;
                    foreach ($intervals as [$start, $end]) {
                        $duringOn += DeviceLog::where('data', $obj->external_id)
                            ->whereBetween('logged_at', [$start, $end])->count();
                    }

                    // Skip objects with no events during ON time
                    if ($duringOn === 0) {
                        continue;
                    }

                    $total = DeviceLog::where('data', $obj->external_id)
                        ->whereBetween('logged_at', [$since, $until])->count();

                    // Intersection time (overlap of object sessions with ON intervals)
                    $overlapMinutes = null;
                    if ($hasPair) {
                        $objLogs = DeviceLog::where('data', $obj->external_id)
                            ->whereBetween('logged_at', [$since, $until])
                            ->whereIn('device_id', $entryIds->merge($exitIds))
                            ->orderBy('logged_at')->get();

                        $sessions = $this->buildSimpleSessions($objLogs, $entryIds, $exitIds, $until);
                        $overlapSeconds = $this->intersectIntervals($sessions, $intervals);
                        $overlapMinutes = (int) round($overlapSeconds / 60);
                    }

                    $crossStats[] = [
                        'object'          => $obj,
                        'during_on'       => $duringOn,
                        'total'           => $total,
                        'percent'         => $total > 0 ? round($duringOn / $total * 100) : null,
                        'overlap_minutes' => $overlapMinutes,
                    ];
                }
            }

            $h = floor($totalOnSeconds / 3600);
            $m = floor(($totalOnSeconds % 3600) / 60);

            $onOffStats[] = [
                'device'        => $device,
                'current_state' => $currentState,
                'total_on_sec'  => $totalOnSeconds,
                'on_h'          => $h,
                'on_m'          => $m,
                'on_percent'    => $onPercent,
                'cross_stats'   => $crossStats,
            ];
        }

        return $onOffStats;
    }

    private function buildSimpleSessions($logs, $entryIds, $exitIds, Carbon $until): array
    {
        $sessions  = [];
        $openEntry = null;

        foreach ($logs as $log) {
            if ($entryIds->contains($log->device_id)) {
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

    // Compute total intersection seconds between two sets of intervals
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

    private function periodLabels(string $period, ?string $customDate): array
    {
        $labels = [
            'today'  => 'Сьогодні',
            'week'   => 'Тиждень',
            'month'  => 'Місяць',
            '3month' => '3 місяці',
        ];
        if ($period === 'day' && $customDate) {
            $labels['day'] = Carbon::parse($customDate)->format('d.m.Y');
        }
        return $labels;
    }

    // ── Admin viewing a user dashboard ────────────────────────────────────────

    public function getDashboardData(User $user, string $period = 'week', ?string $customDate = null, string $deviceView = 'my'): array
    {
        [$since, $until] = $this->parsePeriod($period, $customDate);

        $companyIds = $this->getCompanyIds($user);
        $companies  = \App\Models\Company::with('offices')->whereIn('id', $companyIds)->get();

        $devices = Device::where(function ($q) use ($user, $companyIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('company_id', $companyIds);
            })
            ->with('deviceActions.action')
            ->orderByDesc('created_at')
            ->get();

        $deviceIds  = $devices->pluck('id');
        $allObjects = TrackedObject::whereIn('company_id', $companyIds)->get();

        $readerDeviceIds = Device::whereIn('id', $deviceIds)
            ->where('is_on_off', false)->whereNotNull('is_range_start')->pluck('id');

        $allDataIds          = DeviceLog::whereIn('device_id', $readerDeviceIds)->select('data')->distinct()->orderBy('data')->pluck('data');
        $unregisteredDataIds = $allDataIds->diff($allObjects->pluck('external_id'))->values();

        $objectStats = [];
        foreach ($allObjects as $obj) {
            $lastLog = DeviceLog::where('data', $obj->external_id)->latest('logged_at')->first();
            $objectStats[$obj->id] = [
                'period'      => DeviceLog::where('data', $obj->external_id)->whereBetween('logged_at', [$since, $until])->count(),
                'last_data'   => $lastLog?->data,
                'last_action' => $lastLog?->action?->title ?? $lastLog?->action?->name,
                'last_at'     => $lastLog?->logged_at,
            ];
        }

        $objectsByCompany = $allObjects->groupBy('company_id');

        $deviceStats = [];
        foreach ($devices as $device) {
            $lastLog = DeviceLog::where('device_id', $device->id)->latest('logged_at')->first();
            $deviceStats[$device->id] = [
                'period_count' => DeviceLog::where('device_id', $device->id)->whereBetween('logged_at', [$since, $until])->count(),
                'last_at'      => $lastLog?->logged_at,
                'last_data'    => $lastLog?->data,
            ];
        }

        $onOffStats = $this->buildOnOffStats($devices, $since, $until, $companyIds);

        $logs = DeviceLog::whereIn('device_id', $deviceIds)
            ->with(['device', 'action'])
            ->whereBetween('logged_at', [$since, $until])
            ->latest('logged_at')->take(50)->get()
            ->map(function ($log) use ($allObjects) {
                $log->tracked_object = $allObjects->firstWhere('external_id', $log->data);
                return $log;
            });

        $periodLabels = $this->periodLabels($period, $customDate);

        return compact(
            'companies', 'devices', 'logs', 'period', 'customDate', 'since', 'until',
            'objectsByCompany', 'allObjects', 'objectStats', 'deviceStats', 'onOffStats',
            'unregisteredDataIds', 'allDataIds', 'periodLabels'
        );
    }
}
