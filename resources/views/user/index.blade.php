<x-layout title="Пристрої">
<div class="container py-4">

    @if(isset($viewingAs))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>Ви переглядаєте панель як <strong>{{ $viewingAs->name }}</strong> ({{ $viewingAs->email }})</span>
            <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-dark">← До списку</a>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- ── Period selector ───────────────────────────────────────────────── --}}
    @php $baseUrl = isset($viewingAs) ? route('admin.users.dashboard', $viewingAs) : route('user.index'); @endphp
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <span class="text-muted small fw-semibold">Період:</span>
        @foreach($periodLabels as $key => $label)
            <a href="{{ $baseUrl }}?period={{ $key }}{{ $key === 'day' && !empty($customDate) ? '&date='.$customDate : '' }}"
               class="btn btn-sm {{ $period === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
        <input type="date"
               id="specificDayPicker"
               class="form-control form-control-sm {{ $period === 'day' ? 'border-primary' : '' }}"
               style="max-width:155px"
               value="{{ $customDate ?? '' }}"
               max="{{ date('Y-m-d') }}"
               title="Обрати конкретний день"
               data-base-url="{{ $baseUrl }}">
    </div>
    <script>
    document.getElementById('specificDayPicker').addEventListener('change', function () {
        if (this.value) {
            window.location.href = this.dataset.baseUrl + '?period=day&date=' + this.value;
        }
    });
    </script>

    {{-- ── ON/OFF statistics ──────────────────────────────────────────────── --}}
    @if(!empty($onOffStats))
    <h2 class="mb-3">Статистика ON/OFF</h2>
    @foreach($onOffStats as $stat)
    @php
        $stateColor = $stat['current_state'] === 'on' ? 'success' : ($stat['current_state'] === 'off' ? 'danger' : 'secondary');
        $stateLabel = $stat['current_state'] === 'on' ? 'Увімкнено' : ($stat['current_state'] === 'off' ? 'Вимкнено' : 'Невідомо');
        $h = $stat['on_h'];
        $m = $stat['on_m'];
    @endphp
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold fs-5">{{ $stat['device']->name }}</span>
            <span class="badge bg-{{ $stateColor }} fs-6">{{ $stateLabel }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <div class="border rounded p-3 text-center">
                        <div class="fs-4 fw-bold">{{ $stat['on_percent'] }}%</div>
                        <div class="text-muted small">Час роботи за період</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="border rounded p-3 text-center">
                        <div class="fs-4 fw-bold">{{ $h }}г {{ $m }}хв</div>
                        <div class="text-muted small">Разом увімкнено</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="border rounded p-3 text-center">
                        <div class="fs-4 fw-bold">{{ $periodLabels[$period] ?? $period }}</div>
                        <div class="text-muted small">Вибраний період</div>
                    </div>
                </div>
            </div>

            @if(!empty($stat['cross_stats']))
            <h6 class="mb-2">Суміжна статистика об'єктів</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Об'єкт</th>
                            <th class="text-center">Всього за період</th>
                            <th class="text-center">Під час роботи</th>
                            <th class="text-center">%</th>
                            <th class="text-center">Перетин часу</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stat['cross_stats'] as $cs)
                        <tr>
                            <td>
                                <a href="{{ route('user.tracked-objects.show', $cs['object']) }}" class="text-decoration-none">
                                    {{ $cs['object']->name }}
                                </a>
                            </td>
                            <td class="text-center">{{ $cs['total'] }}</td>
                            <td class="text-center">{{ $cs['during_on'] }}</td>
                            <td class="text-center">
                                @if($cs['percent'] !== null)
                                    <div class="progress" style="height:18px;min-width:60px">
                                        <div class="progress-bar bg-success" style="width:{{ $cs['percent'] }}%">
                                            {{ $cs['percent'] }}%
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                @if($cs['overlap_minutes'] !== null)
                                    @php $oh = floor($cs['overlap_minutes'] / 60); $om = $cs['overlap_minutes'] % 60; @endphp
                                    {{ $oh > 0 ? $oh.'г ' : '' }}{{ $om }}хв
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @endforeach
    @endif

    {{-- ── Devices table ───────────────────────────────────────────────────── --}}
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Пристрій</th>
                    <th class="col-hide-mobile">Тип</th>
                    <th class="text-center">Подій за {{ $periodLabels[$period] ?? $period }}</th>
                    <th class="col-hide-mobile">Остання активність</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                @php $ds = $deviceStats[$device->id] ?? [] @endphp
                <tr>
                    <td>
                        <a href="{{ route('user.devices.show', $device) }}?period={{ $period }}{{ isset($viewingAs) ? '&viewing_as='.$viewingAs->id : '' }}" class="fw-semibold text-decoration-none">
                            {{ $device->name }}
                        </a>
                    </td>
                    <td class="small col-hide-mobile">
                        @if($device->is_on_off)
                            <span class="badge bg-warning text-dark">ON/OFF</span>
                        @elseif(!is_null($device->is_range_start))
                            <span class="badge bg-light text-dark border">
                                {{ $device->is_range_start ? '▶ Початок' : '■ Кінець' }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center fw-semibold">{{ $ds['period_count'] ?? 0 }}</td>
                    <td class="small text-muted col-hide-mobile">
                        @if(!empty($ds['last_at']))
                            {{ \Carbon\Carbon::parse($ds['last_at'])->format('d.m H:i') }}
                            @if(!empty($ds['last_data']))
                                <code class="ms-1">{{ $ds['last_data'] }}</code>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('user.devices.show', $device) }}?period={{ $period }}{{ isset($viewingAs) ? '&viewing_as='.$viewingAs->id : '' }}"
                           class="btn btn-sm btn-outline-primary">→</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted">Немає призначених пристроїв.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-layout>
