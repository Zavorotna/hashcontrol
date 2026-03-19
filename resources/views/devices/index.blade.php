<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Пристрої</h1>
            <a href="{{ route('devices.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                + Новий пристрій
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Фільтри --}}
            <form method="GET" class="flex gap-3">
                <select name="topic_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Всі топіки</option>
                    @foreach($topics as $t)
                        <option value="{{ $t->id }}" {{ request('topic_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
                <select name="session_type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Всі типи сесій</option>
                    @foreach($sessionTypes as $value => $label)
                        <option value="{{ $value }}" {{ request('session_type') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                        class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900">
                    Фільтр
                </button>
            </form>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">MQTT Device ID</th>
                            <th class="px-5 py-3 text-left font-medium">Тип події</th>
                            <th class="px-5 py-3 text-left font-medium">Тип сесії</th>
                            <th class="px-5 py-3 text-left font-medium">Метрика</th>
                            <th class="px-5 py-3 text-left font-medium">Топік</th>
                            <th class="px-5 py-3 text-left font-medium">Магазин</th>
                            <th class="px-5 py-3 text-center font-medium">Статус</th>
                            <th class="px-5 py-3 text-right font-medium">Дії</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($devices as $device)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <span class="font-mono font-medium text-gray-900">{{ $device->mqtt_device_id }}</span>
                                @if($device->name)
                                    <p class="text-xs text-gray-400">{{ $device->name }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium
                                    {{ str_contains($device->type, 'open') || str_contains($device->type, 'on') ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    {{ $device->getTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-xs px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-medium">
                                    {{ $device->getSessionTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-gray-600 text-xs">
                                @if($device->metric_rate_per_hour)
                                    {{ $device->metric_rate_per_hour }} {{ $device->metric_unit }}/год
                                    @if($device->metric_label)
                                        <p class="text-gray-400">{{ $device->metric_label }}</p>
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded">
                                    {{ $device->topic->slug }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $device->store?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-xs px-2.5 py-1 rounded-full
                                    {{ $device->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $device->is_active ? 'Активний' : 'Вимкнено' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('devices.edit', $device) }}"
                                       class="text-xs px-2.5 py-1 border border-gray-300 rounded hover:bg-gray-50">
                                        Ред.
                                    </a>
                                    <form method="POST" action="{{ route('devices.destroy', $device) }}"
                                          onsubmit="return confirm('Видалити?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="text-xs px-2.5 py-1 border border-red-200 text-red-600 rounded hover:bg-red-50">
                                            Вид.
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-10 text-center text-gray-400">
                                    Пристроїв ще немає
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-3 border-t border-gray-100">{{ $devices->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>