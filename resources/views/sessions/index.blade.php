<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Сесії</h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Фільтри --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <form method="GET" action="{{ route('sessions.index') }}" class="flex flex-wrap gap-3 items-end">

                    {{-- Тип сесії --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Тип</label>
                        <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            @foreach($availableTypes as $t)
                                <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>
                                    {{ \App\Models\WorkSession::make(['type' => $t])->type_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Період --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Період</label>
                        <select name="period" onchange="toggleCustomDates(this.value)"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="today"  {{ $period === 'today'  ? 'selected' : '' }}>Сьогодні</option>
                            <option value="week"   {{ $period === 'week'   ? 'selected' : '' }}>Цей тиждень</option>
                            <option value="month"  {{ $period === 'month'  ? 'selected' : '' }}>Цей місяць</option>
                            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Довільний</option>
                        </select>
                    </div>

                    {{-- Довільний період --}}
                    <div id="customDates" class="{{ $period === 'custom' ? 'flex' : 'hidden' }} gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Від</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}"
                                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">До</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}"
                                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>

                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                        Застосувати
                    </button>
                </form>
            </div>

            {{-- KPI --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Всього сесій</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['count'] }}</p>
                </div>
                @if($stats['total_metric'] > 0)
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">
                        Метрика {{ $stats['metric_unit'] ? '(' . $stats['metric_unit'] . ')' : '' }}
                    </p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ round($stats['total_metric'], 2) }}</p>
                </div>
                @endif
            </div>

            {{-- Таблиця --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">
                        {{ \App\Models\WorkSession::make(['type' => $type])->type_label }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium">Об'єкт</th>
                                <th class="px-5 py-3 text-left font-medium">Початок</th>
                                <th class="px-5 py-3 text-left font-medium">Кінець</th>
                                <th class="px-5 py-3 text-center font-medium">Тривалість</th>
                                <th class="px-5 py-3 text-center font-medium">Метрика</th>
                                <th class="px-5 py-3 text-center font-medium">Статус</th>
                                @auth
                                    @if(auth()->user()->isAdmin())
                                        <th class="px-5 py-3 text-center font-medium">Дії</th>
                                    @endif
                                @endauth
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($sessions as $session)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4">
                                    <a href="{{ route('sessions.show', $session) }}"
                                       class="font-medium text-blue-600 hover:underline">
                                        {{ $session->subject?->name ?? '—' }}
                                    </a>
                                    @if($session->device)
                                        <p class="text-xs text-gray-400">{{ $session->device->name ?? $session->device->mqtt_device_id }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $session->started_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $session->ended_at?->format('d.m.Y H:i') ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-center font-medium">
                                    {{ $session->duration_human }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    {{ $session->metric_consumed_label ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if($session->is_active)
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-green-50 text-green-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                            Активна
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Завершена
                                        </span>
                                    @endif
                                </td>
                                @auth
                                    @if(auth()->user()->isAdmin())
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('sessions.edit', $session) }}"
                                               class="text-xs px-2.5 py-1 border border-gray-300 rounded hover:bg-gray-50">
                                                Ред.
                                            </a>
                                            <form method="POST" action="{{ route('sessions.destroy', $session) }}"
                                                  onsubmit="return confirm('Видалити сесію?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="text-xs px-2.5 py-1 border border-red-200 text-red-600 rounded hover:bg-red-50">
                                                    Вид.
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    @endif
                                @endauth
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-gray-400">
                                        Немає сесій за вибраний період
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Пагінація --}}
                @if($sessions->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $sessions->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function toggleCustomDates(val) {
        document.getElementById('customDates').classList.toggle('hidden', val !== 'custom');
        document.getElementById('customDates').classList.toggle('flex', val === 'custom');
    }
    </script>
    @endpush
</x-app-layout>