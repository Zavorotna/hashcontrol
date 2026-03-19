<?php

namespace App\Http\Controllers;

use App\Models\WorkSession;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(private StatsService $stats) {}

    /**
     * Список сесій з фільтрами.
     * GET /sessions?type=generator&period=month
     */
    public function index(Request $request)
    {
        $user = $request->user();

        abort_if(!$user->topic_id && !$user->isAdmin(), 403, 'Топік не призначено');

        $type   = $request->get('type', WorkSession::TYPE_STORE_OPEN);
        $period = $request->get('period', 'month');

        [$from, $to] = match($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week'  => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => [
                Carbon::parse($request->get('date_from', now()->startOfMonth())),
                Carbon::parse($request->get('date_to', now()))->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $query = WorkSession::ofType($type)
            ->forTopic($user->topic_id ?? 0)
            ->with(['subject', 'device'])
            ->latest('started_at');

        // Адмін бачить всі топіки якщо не вибраний конкретний
        if ($user->isAdmin() && $request->filled('topic_id')) {
            $query = WorkSession::ofType($type)
                ->forTopic($request->topic_id)
                ->with(['subject', 'device'])
                ->latest('started_at');
        } elseif ($user->isAdmin() && !$request->filled('topic_id')) {
            $query = WorkSession::ofType($type)
                ->with(['subject', 'device', 'topic'])
                ->latest('started_at');
        }

        $sessions = $query->forPeriod($from, $to)->paginate(20)->withQueryString();

        // Доступні типи для фільтру
        $availableTypes = WorkSession::select('type')
            ->distinct()
            ->pluck('type');

        $stats = [
            'count'        => $sessions->total(),
            'total_metric' => WorkSession::ofType($type)->forPeriod($from, $to)->sum('metric_consumed'),
            'metric_unit'  => WorkSession::ofType($type)->forPeriod($from, $to)->value('metric_unit'),
        ];

        return view('sessions.index', compact(
            'sessions', 'type', 'period', 'from', 'to', 'availableTypes', 'stats'
        ));
    }

    /**
     * Деталі однієї сесії.
     */
    public function show(WorkSession $session, Request $request)
    {
        $user = $request->user();

        abort_if(
            !$user->isAdmin() && $session->topic_id !== $user->topic_id,
            403
        );

        $session->load(['subject', 'device', 'topic']);

        return view('sessions.show', compact('session'));
    }

    /**
     * Форма редагування (тільки адмін).
     */
    public function edit(WorkSession $session)
    {
        $session->load(['subject', 'device', 'topic']);

        return view('sessions.edit', compact('session'));
    }

    /**
     * Оновлення сесії (тільки адмін).
     */
    public function update(Request $request, WorkSession $session)
    {
        $validated = $request->validate([
            'started_at' => 'required|date',
            'ended_at'   => 'nullable|date|after:started_at',
        ]);

        // Перерахувати метрику якщо змінився час
        if ($validated['ended_at'] && $session->metric_rate_per_hour) {
            $seconds  = Carbon::parse($validated['started_at'])
                ->diffInSeconds(Carbon::parse($validated['ended_at']));
            $validated['metric_consumed'] = round($session->metric_rate_per_hour * ($seconds / 3600), 4);
        }

        $session->update($validated);

        return redirect()->route('sessions.index')
            ->with('success', 'Сесію оновлено!');
    }

    /**
     * Видалення сесії (тільки адмін).
     */
    public function destroy(WorkSession $session)
    {
        $session->delete();

        return redirect()->route('sessions.index')
            ->with('success', 'Сесію видалено!');
    }
}