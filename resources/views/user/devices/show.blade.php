<x-layout title="{{ $device->name }}">
<div class="container py-4">

    @if(isset($viewingAs))
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
            <span>Ви переглядаєте панель як <strong>{{ $viewingAs->name }}</strong> ({{ $viewingAs->email }})</span>
            <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-dark">← До списку</a>
        </div>
    @endif

    {{-- ── Alerts ───────────────────────────────────────────────────────────── --}}
    @if(session('command_sent'))
    <div class="alert alert-success d-flex justify-content-between align-items-start">
        <div>
            <strong>Команду надіслано.</strong>
        </div>
        <button type="button" class="btn-close ms-3" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('command_error'))
    <div class="alert alert-danger d-flex justify-content-between align-items-start">
        <div>
            <strong>Помилка MQTT:</strong>
            <div class="mt-1 small">{{ session('command_error') }}</div>
        </div>
        <button type="button" class="btn-close ms-3" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- ── Header ───────────────────────────────────────────────────────────── --}}
    @php
        $rangeRaw = $device->getRawOriginal('is_range_start');
        if ($device->is_on_off) {
            $typeLabel = 'ON/OFF пристрій';
            $typeBadge = 'bg-warning text-dark';
        } elseif ($rangeRaw === true || $rangeRaw === 1) {
            $typeLabel = 'Рідер входу';
            $typeBadge = 'bg-success';
        } elseif ($rangeRaw === false || $rangeRaw === 0) {
            $typeLabel = 'Рідер виходу';
            $typeBadge = 'bg-secondary';
        } else {
            $typeLabel = 'Одиничний пристрій';
            $typeBadge = 'bg-light text-dark border';
        }
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">{{ $device->name }}</h2>
            <span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span>
            @if($device->company)
                <span class="text-muted small ms-2">· {{ $device->company->name }}</span>
            @endif
            {{-- Range pair info --}}
            @if($rangePairPartner)
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <span class="badge bg-success">▶ Вхід: {{ $device->is_range_start ? $device->name : $rangePairPartner->name }}</span>
                    <span class="badge bg-secondary">■ Вихід: {{ $device->is_range_start ? $rangePairPartner->name : $device->name }}</span>
                    <a href="{{ route('user.devices.show', $rangePairPartner) }}" class="btn btn-outline-secondary btn-sm py-0">
                        → {{ $device->is_range_start ? 'Пристрій виходу' : 'Пристрій входу' }}
                    </a>
                </div>
            @endif
        </div>
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">← Назад</a>
    </div>

    {{-- ── Period selector ───────────────────────────────────────────────────── --}}
    @php $baseUrl = route('user.devices.show', $device) . (isset($viewingAs) ? '?viewing_as='.$viewingAs->id : ''); @endphp
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <span class="text-muted small fw-semibold">Період:</span>
        @foreach($periodLabels as $key => $label)
            <a href="{{ $baseUrl }}{{ str_contains($baseUrl, '?') ? '&' : '?' }}period={{ $key }}{{ $key === 'day' && !empty($customDate) ? '&date='.$customDate : '' }}"
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
               data-base-url="{{ $baseUrl }}{{ str_contains($baseUrl, '?') ? '&' : '?' }}">
    </div>
    <script>
    document.getElementById('specificDayPicker').addEventListener('change', function () {
        if (this.value) window.location.href = this.dataset.baseUrl + 'period=day&date=' + this.value;
    });
    </script>

    {{-- ── Range pair stats ────────────────────────────────────────────────────── --}}
    @if($pairStats !== null)
    <div class="card mb-4">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span>Статистика за {{ $periodLabels[$period] ?? $period }}</span>
            <span class="text-muted small">
                {{ $pairStats['total_sessions'] }} {{ $pairStats['total_sessions'] === 1 ? 'сеанс' : ($pairStats['total_sessions'] < 5 ? 'сеанси' : 'сеансів') }}
                &nbsp;·&nbsp;
                {{ $pairStats['total_h'] }}г {{ $pairStats['total_m'] }}хв загалом
            </span>
        </div>
        @if(empty($pairStats['per_object']))
            <div class="card-body text-muted small">Подій за вибраний період не знайдено.</div>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Об'єкт</th>
                        <th class="text-center" style="width:90px">Сеансів</th>
                        <th style="width:130px">Загальний час</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pairStats['per_object'] as $row)
                    <tr>
                        <td class="fw-semibold">
                            {{ $row['name'] }}
                            @if($row['open'])
                                <span class="badge bg-warning text-dark ms-1">всередині</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $row['sessions'] }}</td>
                        <td>
                            @if($row['total_h'] > 0)
                                <span class="fw-semibold">{{ $row['total_h'] }}г</span>
                            @endif
                            {{ $row['total_m'] }}хв
                        </td>
                        <td>
                            @if($row['object'])
                                <a href="{{ route('user.tracked-objects.show', $row['object']) }}?period={{ $period }}{{ !empty($customDate) ? '&date='.$customDate : '' }}"
                                   class="btn btn-sm btn-outline-primary">→</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if(count($pairStats['per_object']) > 1)
                <tfoot class="table-light">
                    <tr>
                        <td class="text-end text-muted small">Разом:</td>
                        <td class="text-center fw-bold">{{ $pairStats['total_sessions'] }}</td>
                        <td class="fw-bold">
                            @if($pairStats['total_h'] > 0){{ $pairStats['total_h'] }}г @endif
                            {{ $pairStats['total_m'] }}хв
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Linked tracked objects ──────────────────────────────────────────── --}}
    @if($linkedObjects->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header fw-semibold">Прив'язані об'єкти</div>
        <ul class="list-group list-group-flush">
            @foreach($linkedObjects as $obj)
            <li class="list-group-item py-2 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-semibold small">{{ $obj->name }}</span>
                    @if($obj->company)
                        <span class="text-muted small ms-2">· {{ $obj->company->name }}</span>
                    @endif
                </div>
                <a href="{{ route('user.tracked-objects.show', $obj) }}" class="btn btn-sm btn-outline-primary">→</a>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ── ON/OFF state + buttons ──────────────────────────────────────────── --}}
    @if($device->is_on_off)
    @php
        $stateColor = match($currentState) {
            'on'  => 'success',
            'off' => 'danger',
            default => 'secondary',
        };
        $stateLabel = match($currentState) {
            'on'  => 'Увімкнено',
            'off' => 'Вимкнено',
            default => 'Невідомо',
        };
        $defaultAction = $device->deviceActions->first()?->action?->name ?? '';
    @endphp
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <span class="badge bg-{{ $stateColor }} fs-5 px-4 py-2">{{ $stateLabel }}</span>
        @if($lastStateLog)
        <span class="text-muted small">
            {{ \Carbon\Carbon::parse($lastStateLog->logged_at)->format('d.m.Y H:i:s') }}
            ({{ \Carbon\Carbon::parse($lastStateLog->logged_at)->diffForHumans() }})
        </span>
        @endif
        <div class="d-flex gap-2 on-off-buttons">
            <form method="POST" action="{{ route('user.devices.send-command', $device) }}" class="flex-fill">
                @csrf
                <input type="hidden" name="action_name" value="{{ $defaultAction }}">
                <input type="hidden" name="data" value="on">
                <button type="submit"
                        class="btn btn-success btn-lg w-100 {{ $currentState === 'on' ? 'opacity-50' : '' }}"
                        {{ $currentState === 'on' ? 'disabled' : '' }}>
                    Ввімкнути
                </button>
            </form>
            <form method="POST" action="{{ route('user.devices.send-command', $device) }}" class="flex-fill">
                @csrf
                <input type="hidden" name="action_name" value="{{ $defaultAction }}">
                <input type="hidden" name="data" value="off">
                <button type="submit"
                        class="btn btn-danger btn-lg w-100 {{ $currentState === 'off' ? 'opacity-50' : '' }}"
                        {{ $currentState === 'off' ? 'disabled' : '' }}>
                    Вимкнути
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Last measurement for single-value devices ───────────────────────── --}}
    @if(!$device->is_on_off && $lastMeasurement)
    <div class="card mb-4" style="max-width:300px">
        <div class="card-header fw-semibold">Останнє значення</div>
        <div class="card-body text-center">
            <div class="fs-2 fw-bold text-primary">{{ $lastMeasurement->data }}</div>
            <div class="text-muted small mt-1">
                {{ \Carbon\Carbon::parse($lastMeasurement->logged_at)->format('d.m.Y H:i:s') }}
            </div>
        </div>
    </div>
    @endif

    {{-- ── Recent activity (full width) ───────────────────────────────────── --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Остання активність</span>
            <span class="badge bg-secondary">{{ $recentLogs->count() }} останніх подій</span>
        </div>

        @if($recentLogs->isEmpty())
            <div class="card-body text-muted small">Активності ще не було.</div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:130px">Час</th>
                        <th>Дія</th>
                        <th>Дані / Об'єкт</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    @php
                        $objectName = $objectMap->get($log->data);
                        $isOnOff    = in_array($log->data, ['on', 'off']);
                    @endphp
                    <tr class="{{ $isOnOff ? ($log->data === 'on' ? 'table-success' : 'table-danger') : '' }}">
                        <td class="text-nowrap text-muted small">
                            {{ \Carbon\Carbon::parse($log->logged_at)->format('d.m H:i:s') }}
                        </td>
                        <td class="small">
                            {{ $log->action?->title ?? $log->action?->name ?? '—' }}
                        </td>
                        <td class="small">
                            @if($isOnOff)
                                <span class="badge bg-{{ $log->data === 'on' ? 'success' : 'danger' }}">
                                    {{ strtoupper($log->data) }}
                                </span>
                            @elseif($objectName)
                                <span class="fw-semibold">{{ $objectName }}</span>
                                <code class="text-muted ms-1 small">{{ $log->data }}</code>
                            @else
                                <code>{{ $log->data }}</code>
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
</x-layout>
