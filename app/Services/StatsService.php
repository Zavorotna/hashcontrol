<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkSession;
use Carbon\Carbon;

class StatsService
{
    /**
     * Статистика для юзера по конкретному типу сесій.
     *
     * Приклад:
     *   $stats = $service->forUser($user, WorkSession::TYPE_GENERATOR, today(), now());
     */
    public function forUser(User $user, string $type, $from = null, $to = null): array
    {
        $query = WorkSession::ofType($type)
            ->forTopic($user->topic_id ?? 0)
            ->with(['subject', 'device'])
            ->latest('started_at');

        if ($from && $to) {
            $query->forPeriod($from, $to);
        }

        $sessions  = $query->get();
        $completed = $sessions->filter(fn($s) => !$s->is_active);
        $active    = $sessions->filter(fn($s) => $s->is_active)->first();

        return [
            'type'           => $type,
            'period_from'    => $from,
            'period_to'      => $to,
            'count'          => $completed->count(),
            'total_seconds'  => $completed->sum('duration_seconds'),
            'total_metric'   => $completed->sum('metric_consumed'),
            'metric_unit'    => $sessions->first()?->metric_unit,
            'metric_label'   => $sessions->first()?->metric_label,
            'active_session' => $active,
            'live_metric'    => $active?->live_metric,
            'sessions'       => $sessions,
        ];
    }

    public function todayForUser(User $user, string $type): array
    {
        return $this->forUser($user, $type, Carbon::today(), Carbon::now());
    }

    public function weeklyForUser(User $user, string $type): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $day    = Carbon::today()->subDays($i);
            $stat   = $this->forUser($user, $type, $day->copy()->startOfDay(), $day->copy()->endOfDay());
            $days[] = [
                'date'          => $day->format('d.m'),
                'count'         => $stat['count'],
                'total_seconds' => $stat['total_seconds'],
                'total_metric'  => $stat['total_metric'],
            ];
        }

        return $days;
    }

    /**
     * Зведена статистика по всіх типах для дашборду.
     */
    public function summaryForUser(User $user): array
    {
        $types = WorkSession::forTopic($user->topic_id ?? 0)
            ->select('type')
            ->distinct()
            ->pluck('type');

        $summary = [];
        foreach ($types as $type) {
            $summary[$type] = $this->todayForUser($user, $type);
        }

        return $summary;
    }
}