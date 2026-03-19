<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Topic;
use App\Models\WorkSession;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $topics = $user->accessibleTopics(); 

        $query = Store::with('topic')->latest();

        if (!$user->isAdmin()) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($request->filled('topic_id')) {
            $query->where('topic_id', $request->topic_id);
        }

        $stores = $query->paginate(20);

        return view('stores.index', compact('stores', 'topics'));
    }

    public function create()
    {
        $topics = Topic::where('is_active', true)->get();

        return view('stores.create', compact('topics'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'topic_id'       => 'required|exists:topics,id',
            'mqtt_device_id' => 'required|string|max:100|unique:stores',
            'name'           => 'required|string|max:255',
            'employee_name'  => 'nullable|string|max:255',
            'location'       => 'nullable|string|max:255',
            'is_active'      => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Store::create($validated);

        return redirect()->route('stores.index')
            ->with('success', 'Магазин додано успішно!');
    }

    public function show(Store $store)
    {
        $store->load('topic', 'devices');

        $sessions = $store->workSessions()
            ->latest('started_at')
            ->paginate(20);

        $statsMonth = $this->getStoreStats($store, now()->startOfMonth(), now()->endOfMonth());
        $statsToday = $this->getStoreStats($store, now()->startOfDay(), now()->endOfDay());

        return view('stores.show', compact('store', 'sessions', 'statsMonth', 'statsToday'));
    }

    public function edit(Store $store)
    {
        $topics = Topic::where('is_active', true)->get();

        return view('stores.edit', compact('store', 'topics'));
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'topic_id'       => 'required|exists:topics,id',
            'mqtt_device_id' => 'required|string|max:100|unique:stores,mqtt_device_id,' . $store->id,
            'name'           => 'required|string|max:255',
            'employee_name'  => 'nullable|string|max:255',
            'location'       => 'nullable|string|max:255',
            'is_active'      => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $store->update($validated);

        return redirect()->route('stores.index')
            ->with('success', 'Магазин оновлено!');
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return redirect()->route('stores.index')
            ->with('success', 'Магазин видалено!');
    }

    // ─────────────────────────────────────────────────────────────

    private function getStoreStats(Store $store, $from, $to): array
    {
        $fromStr = $from->toDateTimeString();
        $toStr   = $to->toDateTimeString();

        $workSeconds = $store->totalWorkSeconds($fromStr, $toStr);

        // Метрика генератора — через сесії топіку типу 'generator'
        $genSeconds = $store->topic
            ->totalSeconds(WorkSession::TYPE_GENERATOR, $fromStr, $toStr);

        $totalMetric = $store->topic
            ->totalMetric(WorkSession::TYPE_GENERATOR, $fromStr, $toStr);

        return [
            'work_hours'      => round($workSeconds / 3600, 2),
            'work_human'      => $this->secondsToHuman($workSeconds),
            'generator_hours' => round($genSeconds / 3600, 2),
            'generator_human' => $this->secondsToHuman($genSeconds),
            'fuel'            => round($totalMetric, 2),
        ];
    }

    private function secondsToHuman(int $seconds): string
    {
        if ($seconds <= 0) return '0 хв';
        $hours   = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? "{$hours} год {$minutes} хв" : "{$minutes} хв";
    }
}