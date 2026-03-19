<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('topic', 'stores')->latest()->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $topics = Topic::where('is_active', true)->get();
        return view('users.create', compact('topics'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:users',
            'password'          => 'required|string|min:8|confirmed',
            'role'              => 'required|in:admin,user',
            'topic_id'          => 'nullable|exists:topics,id',
            'dashboard_widgets' => 'nullable|array',
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role'              => $validated['role'],
            'topic_id'          => $validated['topic_id'] ?? null,
            'dashboard_widgets' => $validated['dashboard_widgets'] ?? User::DEFAULT_WIDGETS,
        ]);

        // Автоматично підв'язати всі магазини топіку
        if ($validated['topic_id']) {
            $storeIds = Store::where('topic_id', $validated['topic_id'])
                ->where('is_active', true)
                ->pluck('id');
            $user->stores()->sync($storeIds);
        }

        return redirect()->route('users.index')->with('success', 'Юзера створено!');
    }

    public function edit(User $user)
    {
        $topics = Topic::where('is_active', true)->get();

        // Магазини поточного топіку юзера
        $topicStores = $user->topic_id
            ? Store::where('topic_id', $user->topic_id)->where('is_active', true)->get()
            : collect();

        return view('users.edit', compact('user', 'topics', 'topicStores'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:users,email,' . $user->id,
            'password'          => 'nullable|string|min:8|confirmed',
            'role'              => 'required|in:admin,user',
            'topic_id'          => 'nullable|exists:topics,id',
            'dashboard_widgets' => 'nullable|array',
        ]);

        $data = [
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'role'              => $validated['role'],
            'topic_id'          => $validated['topic_id'] ?? null,
            'dashboard_widgets' => $validated['dashboard_widgets'] ?? User::DEFAULT_WIDGETS,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        // Якщо змінився топік — автоматично оновити магазини
        if ($validated['topic_id']) {
            $storeIds = Store::where('topic_id', $validated['topic_id'])
                ->where('is_active', true)
                ->pluck('id');
            $user->stores()->sync($storeIds);
        } else {
            // Якщо топік прибрали — прибрати всі магазини
            $user->stores()->detach();
        }

        return redirect()->route('users.index')->with('success', 'Юзера оновлено!');
    }

    public function show(User $user)
    {
        $user->load('topic', 'stores');
        return view('users.show', compact('user'));
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::user()->id) {
            return back()->with('error', 'Не можна видалити себе!');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'Юзера видалено!');
    }
}