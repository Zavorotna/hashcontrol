<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Магазини</h1>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('stores.create') }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                    + Додати магазин
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif

            <form method="GET" class="flex gap-3">
                <select name="topic_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Всі топіки</option>
                    @foreach($topics as $t)
                        <option value="{{ $t->id }}" {{ request('topic_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Фільтр</button>
            </form>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">Назва</th>
                            <th class="px-5 py-3 text-left font-medium">Топік</th>
                            <th class="px-5 py-3 text-left font-medium">MQTT ID</th>
                            <th class="px-5 py-3 text-left font-medium">Відповідальний</th>
                            <th class="px-5 py-3 text-center font-medium">Статус</th>
                            @if(auth()->user()->isAdmin())
                                <th class="px-5 py-3 text-right font-medium">Дії</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($stores as $store)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <a href="{{ route('stores.show', $store) }}" class="font-medium text-blue-600 hover:underline">{{ $store->name }}</a>
                                @if($store->location)<p class="text-xs text-gray-400">{{ $store->location }}</p>@endif
                            </td>
                            <td class="px-5 py-4"><span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $store->topic->slug }}</span></td>
                            <td class="px-5 py-4 font-mono text-xs text-gray-600">{{ $store->mqtt_device_id }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $store->employee_name ?? '—' }}</td>
                            <td class="px-5 py-4 text-center">
                                @php $open = $store->isOpen(); @endphp
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full {{ $open ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $open ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                    {{ $open ? 'Відкрито' : 'Закрито' }}
                                </span>
                            </td>
                            @if(auth()->user()->isAdmin())
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('stores.edit', $store) }}" class="text-xs px-2.5 py-1 border border-gray-300 rounded hover:bg-gray-50">Ред.</a>
                                    <form method="POST" action="{{ route('stores.destroy', $store) }}" onsubmit="return confirm('Видалити?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs px-2.5 py-1 border border-red-200 text-red-600 rounded hover:bg-red-50">Вид.</button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">Магазинів ще немає</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $stores->links() }}
        </div>
    </div>
</x-app-layout>
