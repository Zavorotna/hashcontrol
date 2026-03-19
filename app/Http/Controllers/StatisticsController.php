<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Topic;
use App\Models\WorkSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Адмін бачить всі топіки, юзер — тільки свій
        if ($user->isAdmin()) {
            $topics = Topic::where('is_active', true)->get();
            $selectedId = $request->input('topic_id', $topics->first()?->id);
        } else {
            // Юзер бачить тільки свій топік
            if (!$user->topic_id) {
                return view('statistics.index', [
                    'topics'        => collect(),
                    'topic'         => null,
                    'storeStats'    => [],
                    'genStats'      => null,
                    'extraStats'    => [],
                    'storeChartData'=> [],
                    'period'        => 'month',
                    'from'          => now()->startOfMonth()->toDateTimeString(),
                    'to'            => now()->endOfMonth()->toDateTimeString(),
                ]);
            }

            $topics     = Topic::where('id', $user->topic_id)->where('is_active', true)->get();
            $selectedId = $user->topic_id;
        }

        $topic       = Topic::with('stores')->find($selectedId);
        $period      = $request->input('period', 'month');
        [$from, $to] = $this->getPeriodDates($period, $request);

        // Юзер не може дивитись чужий топік
        if (!$user->isAdmin() && $topic?->id !== $user->topic_id) {
            abort(403);
        }

        if (!$topic) {
            return view('statistics.index', [
                'topics'        => $topics,
                'topic'         => null,
                'storeStats'    => [],
                'genStats'      => null,
                'extraStats'    => [],
                'storeChartData'=> [],
                'period'        => $period,
                'from'          => $from,
                'to'            => $to,
            ]);
        }

        // Юзер бачить тільки свої магазини
        $stores = $user->isAdmin()
            ? $topic->stores
            : $topic->stores->filter(fn($s) => $user->canAccessStore($s));

        // ── Статистика по магазинах ──
        $storeStats = $stores->map(function (Store $store) use ($from, $to) {
            $workSec       = $store->totalWorkSeconds($from, $to);
            $sessionsCount = $store->workSessions()
                ->ofType(WorkSession::TYPE_STORE_OPEN)
                ->where('started_at', '>=', $from)
                ->where('started_at', '<=', $to)
                ->count();

            return [
                'store'          => $store,
                'sessions_count' => $sessionsCount,
                'work_seconds'   => $workSec,
                'work_human'     => $this->secondsToHuman($workSec),
                'work_hours'     => round($workSec / 3600, 2),
                'is_open'        => $store->isOpen(),
            ];
        });

        // ── Генератор ──
        $genStats = null;
        if ($topic->has_generator) {
            $genSessions = $topic->workSessions()
                ->ofType(WorkSession::TYPE_GENERATOR)
                ->completed()
                ->where('started_at', '>=', $from)
                ->where('started_at', '<=', $to)
                ->get();

            $totalGenSec   = (int) $genSessions->sum('duration_seconds');
            $totalMetric   = (float) $genSessions->sum('metric_consumed');
            $metricUnit    = $genSessions->first()?->metric_unit ?? 'л';
            $activeSession = $topic->activeSession(WorkSession::TYPE_GENERATOR);

            $genStats = [
                'sessions_count' => $genSessions->count(),
                'total_seconds'  => $totalGenSec,
                'total_human'    => $this->secondsToHuman($totalGenSec),
                'total_metric'   => round($totalMetric, 2),
                'metric_unit'    => $metricUnit,
                'active_session' => $activeSession,
                'chart_data'     => $this->buildGeneratorChartData($genSessions),
            ];
        }

        $extraStats     = $this->buildExtraStats($topic, $from, $to);
        $storeChartData = $this->buildStoreChartData($stores, $from, $to);

        return view('statistics.index', compact(
            'topics', 'topic', 'storeStats', 'genStats', 'extraStats',
            'period', 'from', 'to', 'storeChartData'
        ));
    }

    // ─────────────────────────────────────────────────────────────

    private function buildExtraStats(Topic $topic, string $from, string $to): array
    {
        $skip = [WorkSession::TYPE_STORE_OPEN, WorkSession::TYPE_GENERATOR];

        $types = $topic->workSessions()
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->filter(fn($t) => !in_array($t, $skip));

        $stats = [];
        foreach ($types as $type) {
            $sessions = $topic->workSessions()
                ->ofType($type)
                ->completed()
                ->where('started_at', '>=', $from)
                ->where('started_at', '<=', $to)
                ->get();

            $stats[$type] = [
                'type'           => $type,
                'label'          => $sessions->first()?->type_label ?? $type,
                'sessions_count' => $sessions->count(),
                'total_seconds'  => (int) $sessions->sum('duration_seconds'),
                'total_human'    => $this->secondsToHuman((int) $sessions->sum('duration_seconds')),
                'total_metric'   => round((float) $sessions->sum('metric_consumed'), 2),
                'metric_unit'    => $sessions->first()?->metric_unit,
                'active_session' => $topic->activeSession($type),
            ];
        }

        return $stats;
    }

    private function getPeriodDates(string $period, Request $request): array
    {
        return match($period) {
            'today'  => [now()->startOfDay()->toDateTimeString(),   now()->endOfDay()->toDateTimeString()],
            'week'   => [now()->startOfWeek()->toDateTimeString(),  now()->endOfWeek()->toDateTimeString()],
            'month'  => [now()->startOfMonth()->toDateTimeString(), now()->endOfMonth()->toDateTimeString()],
            'custom' => [
                Carbon::parse($request->input('date_from', now()->startOfMonth()))->toDateTimeString(),
                Carbon::parse($request->input('date_to', now()))->endOfDay()->toDateTimeString(),
            ],
            default  => [now()->startOfMonth()->toDateTimeString(), now()->endOfMonth()->toDateTimeString()],
        };
    }

    private function secondsToHuman(int $seconds): string
    {
        if ($seconds <= 0) return '0 хв';
        $hours   = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return $hours > 0 ? "{$hours} год {$minutes} хв" : "{$minutes} хв";
    }

    private function buildGeneratorChartData($sessions): array
    {
        return $sessions->map(fn($s) => [
            'date'   => Carbon::parse($s->started_at)->format('d.m'),
            'hours'  => round($s->duration_seconds / 3600, 2),
            'metric' => $s->metric_consumed,
            'unit'   => $s->metric_unit,
        ])->values()->toArray();
    }

    private function buildStoreChartData($stores, string $from, string $to): array
    {
        $labels   = [];
        $start    = Carbon::parse($from);
        $end      = Carbon::parse($to);
        $colors   = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'];
        $datasets = [];

        foreach ($stores as $i => $store) {
            $data    = [];
            $current = $start->copy();

            while ($current <= $end) {
                $dayStart = $current->copy()->startOfDay()->toDateTimeString();
                $dayEnd   = $current->copy()->endOfDay()->toDateTimeString();

                $seconds = $store->totalWorkSeconds($dayStart, $dayEnd);
                $data[]  = round($seconds / 3600, 2);

                $label = $current->format('d.m');
                if (!in_array($label, $labels)) {
                    $labels[] = $label;
                }

                $current->addDay();
            }

            $datasets[] = [
                'label' => $store->name,
                'data'  => $data,
                'color' => $colors[$i % count($colors)],
            ];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }
}