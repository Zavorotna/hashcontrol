<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Топіки (MQTT)</h1>
            <a href="{{ route('topics.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">+ Новий топік</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">Назва / Slug</th>
                            <th class="px-5 py-3 text-center font-medium">Магазини</th>
                            <th class="px-5 py-3 text-center font-medium">Пристрої</th>
                            <th class="px-5 py-3 text-center font-medium">Генератор</th>
                            <th class="px-5 py-3 text-center font-medium">Норма пального</th>
                            <th class="px-5 py-3 text-center font-medium">Статус</th>
                            <th class="px-5 py-3 text-right font-medium">Дії</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($topics as $topic)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-900">{{ $topic->name }}</p>
                                <span class="font-mono text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">{{ $topic->slug }}</span>
                            </td>
                            <td class="px-5 py-4 text-center font-medium">{{ $topic->stores_count }}</td>
                            <td class="px-5 py-4 text-center font-medium">{{ $topic->devices_count }}</td>
                            <td class="px-5 py-4 text-center">{{ $topic->has_generator ? '⚡' : '—' }}</td>
                            <td class="px-5 py-4 text-center text-gray-700">{{ $topic->has_generator ? $topic->fuel_rate_per_hour . ' л/год' : '—' }}</td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $topic->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $topic->is_active ? 'Активний' : 'Вимкнено' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('topics.edit', $topic) }}" class="text-xs px-2.5 py-1 border border-gray-300 rounded hover:bg-gray-50">Ред.</a>
                                    <form method="POST" action="{{ route('topics.destroy', $topic) }}" onsubmit="return confirm('Видалити топік?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs px-2.5 py-1 border border-red-200 text-red-600 rounded hover:bg-red-50">Вид.</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">Топіків ще немає</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-3 border-t border-gray-100">{{ $topics->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
