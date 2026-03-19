<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Статистика</h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Фільтри --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <form method="GET" action="{{ route('statistics') }}" class="flex flex-wrap gap-3 items-end">
                    @if($topics->count() > 1)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Топік</label>
                        <select name="topic_id"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            @foreach($topics as $t)
                                <option value="{{ $t->id }}" {{ $topic?->id == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Період</label>
                        <select name="period" id="periodSelect" onchange="toggleCustomDates(this.value)"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="today"  {{ $period === 'today'  ? 'selected' : '' }}>Сьогодні</option>
                            <option value="week"   {{ $period === 'week'   ? 'selected' : '' }}>Цей тиждень</option>
                            <option value="month"  {{ $period === 'month'  ? 'selected' : '' }}>Цей місяць</option>
                            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Довільний</option>
                        </select>
                    </div>

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

            @if(!$topic)
                <div class="text-center py-16 text-gray-400">Топік не призначено. Зверніться до адміністратора.</div>
            @else

            {{-- Заголовок --}}
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-lg font-semibold text-gray-900">{{ $topic->name }}</h2>
                <span class="font-mono text-sm text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ $topic->slug }}</span>
                @if($genStats)
                    <span class="px-2 py-0.5 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded text-xs font-medium">⚡ Генератор</span>
                @endif
                @foreach($extraStats as $type => $stat)
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-xs font-medium">
                        {{ $stat['label'] }}
                        @if($stat['active_session'])
                            <span class="inline-block w-1.5 h-1.5 bg-green-500 rounded-full ml-1 animate-pulse"></span>
                        @endif
                    </span>
                @endforeach
            </div>

            {{-- KPI блоки — тільки якщо є дані --}}
            @php
                $hasAnyStats = $genStats || !empty($extraStats);
            @endphp

            @if($hasAnyStats)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min(2 + count($extraStats) + ($genStats ? 1 : 0), 4) }} gap-4">

                {{-- Генератор --}}
                @if($genStats)
                <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-700">⚡ Генератор</p>
                        @if($genStats['active_session'])
                            <span class="flex items-center gap-1 text-xs text-yellow-600 font-medium">
                                <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                Працює з {{ $genStats['active_session']->started_at->format('H:i') }}
                            </span>
                        @else
                            <span class="text-xs text-gray-400">Вимкнено</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="bg-yellow-50 rounded-lg p-2">
                            <p class="text-xl font-bold text-yellow-800">{{ $genStats['sessions_count'] }}</p>
                            <p class="text-xs text-yellow-600">сесій</p>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-2">
                            <p class="text-sm font-bold text-yellow-800">{{ $genStats['total_human'] }}</p>
                            <p class="text-xs text-yellow-600">загалом</p>
                        </div>
                    </div>
                    @if($genStats['total_metric'] > 0)
                    <div class="bg-orange-50 rounded-lg p-2 text-center">
                        <p class="text-xl font-bold text-orange-700">
                            {{ $genStats['total_metric'] }}
                            <span class="text-sm font-normal">{{ $genStats['metric_unit'] }}</span>
                        </p>
                        <p class="text-xs text-orange-500">витрачено</p>
                    </div>
                    @endif
                    @if($genStats['active_session']?->live_metric)
                    <div class="text-center text-xs text-orange-400">
                        зараз ~{{ $genStats['active_session']->live_metric }} {{ $genStats['metric_unit'] }}
                    </div>
                    @endif
                </div>
                @endif

                {{-- Додаткові типи (охорона, холодильник тощо) --}}
                @foreach($extraStats as $type => $stat)
                <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-700">{{ $stat['label'] }}</p>
                        @if($stat['active_session'])
                            <span class="flex items-center gap-1 text-xs text-green-600 font-medium">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                Активно
                            </span>
                        @else
                            <span class="text-xs text-gray-400">Неактивно</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="bg-blue-50 rounded-lg p-2">
                            <p class="text-xl font-bold text-blue-800">{{ $stat['sessions_count'] }}</p>
                            <p class="text-xs text-blue-600">сесій</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-2">
                            <p class="text-sm font-bold text-blue-800">{{ $stat['total_human'] }}</p>
                            <p class="text-xs text-blue-600">загалом</p>
                        </div>
                    </div>
                    @if($stat['total_metric'] > 0)
                    <div class="bg-blue-50 rounded-lg p-2 text-center">
                        <p class="text-xl font-bold text-blue-700">
                            {{ $stat['total_metric'] }}
                            <span class="text-sm font-normal">{{ $stat['metric_unit'] }}</span>
                        </p>
                        <p class="text-xs text-blue-500">витрачено</p>
                    </div>
                    @endif
                </div>
                @endforeach

            </div>
            @endif

            {{-- Таблиця магазинів --}}
            @if($storeStats->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">
                        {{ $storeStats->count() === 1 ? $storeStats->first()['store']->name : 'Магазини' }}
                    </h3>
                    <span class="text-sm text-gray-500">
                        {{ match($period) {
                            'today'  => 'Сьогодні',
                            'week'   => 'Цей тиждень',
                            'month'  => 'Цей місяць',
                            default  => 'Довільний період',
                        } }}
                    </span>
                </div>

                @if($storeStats->count() === 1)
                {{-- Один магазин — великий вигляд --}}
                @php $s = $storeStats->first(); @endphp
                <div class="p-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-gray-900">{{ $s['sessions_count'] }}</p>
                        <p class="text-xs text-gray-400 mt-1">сесій відкриття</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ $s['work_human'] }}</p>
                        <p class="text-xs text-gray-400 mt-1">загальний час</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 mt-1">{{ $s['store']->employee_name ?? '—' }}</p>
                        <p class="text-xs text-gray-400 mt-1">відповідальний</p>
                    </div>
                    <div class="text-center">
                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold px-3 py-1.5 rounded-full
                            {{ $s['is_open'] ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            <span class="w-2 h-2 rounded-full {{ $s['is_open'] ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }}"></span>
                            {{ $s['is_open'] ? 'Відкрито' : 'Закрито' }}
                        </span>
                    </div>
                </div>

                @else
                {{-- Кілька магазинів — таблиця --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium">Магазин</th>
                                <th class="px-5 py-3 text-left font-medium">Відповідальний</th>
                                <th class="px-5 py-3 text-center font-medium">Сесій</th>
                                <th class="px-5 py-3 text-center font-medium">Час роботи</th>
                                <th class="px-5 py-3 text-center font-medium">Статус</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($storeStats as $stat)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4">
                                    <a href="{{ route('stores.show', $stat['store']) }}"
                                       class="font-medium text-blue-600 hover:underline">
                                        {{ $stat['store']->name }}
                                    </a>
                                    @if($stat['store']->location)
                                        <p class="text-xs text-gray-400">{{ $stat['store']->location }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $stat['store']->employee_name ?? '—' }}</td>
                                <td class="px-5 py-4 text-center font-medium">{{ $stat['sessions_count'] }}</td>
                                <td class="px-5 py-4 text-center font-medium text-gray-900">{{ $stat['work_human'] }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full
                                        {{ $stat['is_open'] ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $stat['is_open'] ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                        {{ $stat['is_open'] ? 'Відкрито' : 'Закрито' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endif

            {{-- Графік магазинів — тільки якщо більше 1 дня і є дані --}}
            @if(!empty($storeChartData['labels']) && count($storeChartData['labels']) > 1)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-900 mb-4">Графік роботи (год/день)</h3>
                <canvas id="storeChart" height="80"></canvas>
            </div>
            @endif

            {{-- Графік генератора --}}
            @if($genStats && count($genStats['chart_data']) > 1)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-900 mb-4">Генератор по сесіях</h3>
                <canvas id="genChart" height="60"></canvas>
            </div>
            @endif

            @endif
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    function toggleCustomDates(val) {
        document.getElementById('customDates').classList.toggle('hidden', val !== 'custom');
        document.getElementById('customDates').classList.toggle('flex', val === 'custom');
    }

    @if(!empty($storeChartData['labels']) && count($storeChartData['labels']) > 1)
    new Chart(document.getElementById('storeChart'), {
        type: 'bar',
        data: {
            labels: @json($storeChartData['labels']),
            datasets: @json($storeChartData['datasets']).map(d => ({
                label: d.label,
                data: d.data,
                backgroundColor: d.color + '33',
                borderColor: d.color,
                borderWidth: 2,
                borderRadius: 4,
            }))
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Годин' } } }
        }
    });
    @endif

    @if($genStats && count($genStats['chart_data']) > 1)
    new Chart(document.getElementById('genChart'), {
        type: 'bar',
        data: {
            labels: @json(array_column($genStats['chart_data'], 'date')),
            datasets: [
                {
                    label: 'Час роботи (год)',
                    data: @json(array_column($genStats['chart_data'], 'hours')),
                    backgroundColor: '#FCD34D44',
                    borderColor: '#F59E0B',
                    borderWidth: 2,
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    label: '{{ $genStats["metric_unit"] ?? "Метрика" }}',
                    data: @json(array_column($genStats['chart_data'], 'metric')),
                    backgroundColor: '#FB923C44',
                    borderColor: '#EA580C',
                    borderWidth: 2,
                    type: 'line',
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y:  { beginAtZero: true, title: { display: true, text: 'Годин' } },
                y1: { beginAtZero: true, position: 'right', title: { display: true, text: '{{ $genStats["metric_unit"] ?? "" }}' }, grid: { drawOnChartArea: false } }
            }
        }
    });
    @endif
    </script>
    @endpush
</x-app-layout>