<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Користувачі</h1>
            <a href="{{ route('users.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">+ Новий юзер</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3">{{ session('error') }}</div>
            @endif

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">Ім'я / Email</th>
                            <th class="px-5 py-3 text-center font-medium">Роль</th>
                            <th class="px-5 py-3 text-left font-medium">Топік</th>
                            <th class="px-5 py-3 text-left font-medium">Магазини</th>
                            <th class="px-5 py-3 text-left font-medium">Віджети</th>
                            <th class="px-5 py-3 text-right font-medium">Дії</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium
                                    {{ $user->isAdmin() ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $user->isAdmin() ? 'Admin' : 'User' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                {{ $user->topic?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4">
                                @if($user->stores->isEmpty())
                                    <span class="text-gray-400 text-xs">{{ $user->isAdmin() ? 'всі' : '—' }}</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->stores->take(3) as $store)
                                            <span class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ $store->name }}</span>
                                        @endforeach
                                        @if($user->stores->count() > 3)
                                            <span class="text-xs text-gray-400">+{{ $user->stores->count() - 3 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($user->activeWidgets() as $widget)
                                        <span class="text-xs bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded">
                                            {{ \App\Models\User::WIDGETS[$widget] ?? $widget }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('users.edit', $user) }}" class="text-xs px-2.5 py-1 border border-gray-300 rounded hover:bg-gray-50">Ред.</a>
                                    @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Видалити юзера?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs px-2.5 py-1 border border-red-200 text-red-600 rounded hover:bg-red-50">Вид.</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">Юзерів ще немає</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-3 border-t border-gray-100">{{ $users->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
