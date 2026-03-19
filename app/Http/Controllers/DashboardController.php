<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Topic;
use App\Models\WorkSession;
use App\Services\StatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private StatsService $stats) {}

    public function index(Request $request)
    {
        $user   = $request->user();
        $topics = Topic::where('is_active', true)->get();
        $stores = $user->accessibleStores($user->topic_id);

        // Зведена статистика по всіх типах сесій для дашборду
        $summary = $this->stats->summaryForUser($user);

        // Активні сесії прямо зараз
        $activeSessions = WorkSession::active()
            ->forTopic($user->topic_id ?? 0)
            ->with(['subject', 'device'])
            ->get();

        // Графік за тиждень по кожному типу
        $weekly = [];
        foreach (array_keys($summary) as $type) {
            $weekly[$type] = $this->stats->weeklyForUser($user, $type);
        }

        return view('dashboard', compact(
            'topics', 'stores', 'summary', 'activeSessions', 'weekly', 'user'
        ));
    }
}