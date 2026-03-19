<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Сесії</h1>
                <div class="flex gap-4 mt-2">
                    <a href="{{ route('sessions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Магазини</a>
                    <a href="{{ route('sessions.index') }}" class="text-sm font-medium text-blue-600 border-b-2 border-blue-600 pb-0.5">Генератор</a>
                </div>
            </div>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('sessions.index') }}" class="px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600 transition">+ Додати вручну</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif

            <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Топік</label>
                    <select name="topic_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Всі</option>
                        @foreach($topics as $topic)
                            <option value="{{ $topic->id }}" {{ request('topic_id') == $topic->id ? 'selected' : '' }}>{{ $topic->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Від</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">До</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900">Фільтр</button>
                <a href="{{ route('sessions.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Скинути</a>
            </form>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">Топік</th>
                            <th class="px-5 py-3 text-left font-medium">Початок</th>
                            <th class="px-5 py-3 text-left font-medium">Кінець</th>
                            <th class="px-5 py-3 text-center font-medium">Тривалість</th>
                            <th class="px-5 py-3 text-center font-medium">Пальне (л)</th>
                            <th class="px-5 py-3 text-center font-medium">Статус</th>
                            @if(auth()->user()->isAdmin())<th class="px-5 py-3 text-right font-medium">Дії</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($sessions as $session)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium">{{ $session->topic->name }}</td>
                            <td class="px-5 py-3 tabular-nums">{{ $session->started_at->format('d.m.Y H:i') }}</td>
                            <td class="px-5 py-3 tabular-nums">{{ $session->stopped_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td class="px-5 py-3 text-center font-medium">{{ $session->duration_human }}</td>
                            <td class="px-5 py-3 text-center font-semibold text-orange-600">{{ $session->fuel_consumed_total ?? '—' }}</td>
                            <td class="px-5 py-3 text-center">
                                @if($session->isRunning())
                                    <span class="inline-flex items-center gap-1 text-xs bg-yellow-50 text-yellow-700 px-2.5 py-1 rounded-full font-semibold">
                                        <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full animate-pulse"></span>працює
                                    </span>
                                @else
                                    <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full">завершено</span>
                                @endif
                            </td>
                            @if(auth()->user()->isAdmin())
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('sessions.edit', $session) }}" class="text-xs px-2.5 py-1 border border-gray-300 rounded hover:bg-gray-50">Ред.</a>
                                    <form method="POST" action="{{ route('sessions.destroy', $session) }}" onsubmit="return confirm('Видалити?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs px-2.5 py-1 border border-red-200 text-red-600 rounded hover:bg-red-50">Вид.</button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">Сесій не знайдено</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-3 border-t border-gray-100">{{ $sessions->appends(request()->query())->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
