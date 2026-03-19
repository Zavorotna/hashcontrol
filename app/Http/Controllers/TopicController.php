<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\WorkSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TopicController extends Controller
{
    public function index()
    {
        Topic::syncFromMqttMessages();

        $topics = Topic::withCount(['stores', 'devices'])
            ->latest()
            ->paginate(15);

        return view('topics.index', compact('topics'));
    }

    public function create()
    {
        return view('topics.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:100|unique:topics|regex:/^[a-z0-9_\/-]+$/',
            'description'   => 'nullable|string',
            'has_generator' => 'boolean',
            'is_active'     => 'boolean',
        ]);

        $validated['slug']          = Str::slug($validated['slug'], '/');
        $validated['has_generator'] = $request->boolean('has_generator');
        $validated['is_active']     = $request->boolean('is_active', true);

        Topic::create($validated);

        return redirect()->route('topics.index')
            ->with('success', 'Топік створено успішно!');
    }

    public function show(Topic $topic)
    {
        $topic->load(['stores', 'devices']);

        // Останні 10 сесій топіку (всі типи)
        $recentSessions = $topic->workSessions()
            ->with(['device', 'subject'])
            ->latest('started_at')
            ->limit(10)
            ->get();

        // Активна сесія генератора якщо є
        $activeGenerator = $topic->has_generator
            ? $topic->activeSession(WorkSession::TYPE_GENERATOR)
            : null;

        return view('topics.show', compact('topic', 'recentSessions', 'activeGenerator'));
    }

    public function edit(Topic $topic)
    {
        return view('topics.edit', compact('topic'));
    }

    public function update(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:100|unique:topics,slug,' . $topic->id . '|regex:/^[a-z0-9_\/-]+$/',
            'description'   => 'nullable|string',
            'has_generator' => 'boolean',
            'is_active'     => 'boolean',
        ]);

        $validated['has_generator'] = $request->boolean('has_generator');
        $validated['is_active']     = $request->boolean('is_active');

        $topic->update($validated);

        return redirect()->route('topics.index')
            ->with('success', 'Топік оновлено!');
    }

    public function destroy(Topic $topic)
    {
        $topic->delete();

        return redirect()->route('topics.index')
            ->with('success', 'Топік видалено!');
    }
}