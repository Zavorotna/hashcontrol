<x-layout title="Панель користувача">
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
    @php
        $baseUrl = isset($viewingAs)
            ? route('admin.users.dashboard', $viewingAs)
            : route('user.index');
        $periodLabels = [
            'today'  => 'Сьогодні',
            'week'   => 'Тиждень',
            'month'  => 'Місяць',
            '3month' => '3 місяці',
        ];
        if ($period === 'day' && !empty($customDate)) {
            $periodLabels['day'] = \Carbon\Carbon::parse($customDate)->format('d.m.Y');
        }
    @endphp
    @php $dvParam = '&device_view=' . ($deviceView ?? 'my'); @endphp
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <span class="text-muted small fw-semibold">Період:</span>
        @foreach($periodLabels as $key => $label)
            <a href="{{ $baseUrl }}?period={{ $key }}{{ $key === 'day' && !empty($customDate) ? '&date='.$customDate : '' }}{{ $dvParam }}"
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
               data-base-url="{{ $baseUrl }}"
               data-dv-param="{{ $dvParam }}">
    </div>
    <script>
    document.getElementById('specificDayPicker').addEventListener('change', function () {
        if (this.value) {
            window.location.href = this.dataset.baseUrl + '?period=day&date=' + this.value + (this.dataset.dvParam || '');
        }
    });
    </script>

    {{-- ── Device view toggle ──────────────────────────────────────────────── --}}
    @php
        $dvBase = isset($viewingAs)
            ? route('admin.users.dashboard', $viewingAs)
            : route('user.index');
    @endphp
    <div class="d-flex align-items-center gap-2 mb-4">
        <span class="text-muted small fw-semibold">Пристрої:</span>
        <a href="{{ $dvBase }}?period={{ $period }}{{ !empty($customDate) ? '&date='.$customDate : '' }}&device_view=my"
           class="btn btn-sm {{ ($deviceView ?? 'my') === 'my' ? 'btn-primary' : 'btn-outline-secondary' }}">
            Мої пристрої
        </a>
        <a href="{{ $dvBase }}?period={{ $period }}{{ !empty($customDate) ? '&date='.$customDate : '' }}&device_view=all"
           class="btn btn-sm {{ ($deviceView ?? 'my') === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
            Усі пристрої компаній
        </a>
    </div>


    {{-- ── ON/OFF statistics (generators, exhausts, etc.) ───────────────── --}}
    @if(!empty($onOffStats))
    <h2 class="mb-3">Статистика ON/OFF пристроїв</h2>
    @foreach($onOffStats as $stat)
    @php
        $stateColor = $stat['current_state'] === 'on' ? 'success' : ($stat['current_state'] === 'off' ? 'danger' : 'secondary');
        $stateLabel = $stat['current_state'] === 'on' ? 'Увімкнено' : ($stat['current_state'] === 'off' ? 'Вимкнено' : 'Невідомо');
        $h = floor($stat['total_on_sec'] / 3600);
        $m = floor(($stat['total_on_sec'] % 3600) / 60);
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
                        <div class="fs-4 fw-bold">{{ $periodLabels[$period] }}</div>
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
                            <th class="text-center">Всього подій за період</th>
                            <th class="text-center">Під час роботи пристрою</th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stat['cross_stats'] as $cs)
                        <tr>
                            <td>
                                <a href="{{ route('user.tracked-objects.show', $cs['object']) }}" class="text-decoration-none">
                                    {{ $cs['object']->name }}
                                </a>
                                <span class="text-muted small ms-1">({{ $cs['object']->external_id }})</span>
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

    {{-- ── Companies and objects ──────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Ваші компанії та об'єкти</h2>
        <a href="{{ route('user.tracked-objects.create') }}" class="btn btn-outline-primary btn-sm">+ Додати об'єкт</a>
    </div>

    @php
    $typeLabels = [
        'shop'        => 'Магазини / приміщення',
        'worker'      => 'Працівники',
        'generator'   => 'Генератори',
        'thermometer' => 'Термометри',
        'fridge'      => 'Холодильники',
        'counter'     => 'Лічильники',
        'other'       => 'Інше',
    ];
    @endphp

    @forelse($companies as $company)
    <div class="card mb-4">
        <div class="card-header fw-bold fs-5">{{ $company->name }}</div>
        <div class="card-body">

            @php
                $companyObjects = $objectsByCompany->get($company->id, collect());
            @endphp

            @if($companyObjects->isEmpty())
                <p class="text-muted mb-0">
                    Об'єктів не зареєстровано.
                    <a href="{{ route('user.tracked-objects.create') }}">Додати</a>
                </p>
            @else
                @foreach($companyObjects->groupBy('type') as $type => $group)
                <h6 class="mt-3 mb-2">{{ $typeLabels[$type] ?? ucfirst($type) }}</h6>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Назва</th>
                                <th>ID (MQTT)</th>
                                <th class="text-center" style="width:100px">За {{ $periodLabels[$period] }}</th>
                                <th>Остання подія</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group as $obj)
                            @php $st = $objectStats[$obj->id] ?? [] @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('user.tracked-objects.show', $obj) }}" class="fw-semibold text-decoration-none">
                                        {{ $obj->name }}
                                    </a>
                                    @if($obj->name === $obj->external_id)
                                        <br><a href="{{ route('user.tracked-objects.edit', $obj) }}" class="small text-warning">Заповнити дані</a>
                                    @elseif($obj->tenant_name)
                                        <br><small class="text-muted">{{ $obj->tenant_name }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $obj->external_id }}</code></td>
                                <td class="text-center fw-semibold">{{ $st['period'] ?? 0 }}</td>
                                <td class="small">
                                    @if(!empty($st['last_data']))
                                        <code>{{ $st['last_data'] }}</code>
                                        @if(!empty($st['last_action']))
                                            <span class="text-muted">— {{ $st['last_action'] }}</span>
                                        @endif
                                        @if(!empty($st['last_at']))
                                            <br><span class="text-muted">{{ \Carbon\Carbon::parse($st['last_at'])->format('d.m H:i') }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('user.tracked-objects.show', $obj) }}"
                                       class="btn btn-sm btn-outline-primary">→</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endforeach
            @endif

        </div>
    </div>
    @empty
        <p class="text-muted">Немає компаній.</p>
    @endforelse

    {{-- ── Devices and their activity ─────────────────────────────────────── --}}
    <h2 class="mb-3 mt-2">Ваші пристрої</h2>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Пристрій</th>
                    <th>Device ID</th>
                    <th>Тип</th>
                    <th class="text-center">Подій за {{ $periodLabels[$period] }}</th>
                    <th>Остання активність</th>
                    <th>Дії</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                @php $ds = $deviceStats[$device->id] ?? [] @endphp
                <tr>
                    <td>
                        <a href="{{ route('user.devices.show', $device) }}" class="fw-semibold text-decoration-none">
                            {{ $device->name }}
                        </a>
                    </td>
                    <td><code class="text-muted small">{{ $device->device_id }}</code></td>
                    <td class="small">
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
                    <td class="small text-muted">
                        @if(!empty($ds['last_at']))
                            {{ \Carbon\Carbon::parse($ds['last_at'])->format('d.m H:i') }}
                            @if(!empty($ds['last_data']))
                                <code class="ms-1">{{ $ds['last_data'] }}</code>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($device->deviceActions->isNotEmpty())
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($device->deviceActions as $da)
                                <span class="badge bg-light text-dark border small">
                                    {{ $da->action->title ?? $da->action->name }}
                                    @if($da->payload) <span class="text-muted">({{ $da->payload }})</span> @endif
                                </span>
                            @endforeach
                        </div>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('user.devices.show', $device) }}"
                           class="btn btn-sm btn-outline-primary">→</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-muted">Немає призначених пристроїв.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Recent events ───────────────────────────────────────────────────── --}}
    <h2 class="mb-3">Останні події</h2>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead class="table-light">
                <tr>
                    <th>Час</th>
                    <th>Пристрій</th>
                    <th>Дія</th>
                    <th>Об'єкт / ID</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-muted small text-nowrap">{{ \Carbon\Carbon::parse($log->logged_at)->format('d.m H:i:s') }}</td>
                    <td class="small">{{ $log->device->name }}</td>
                    <td class="small">{{ $log->action?->title ?? $log->action?->name ?? '—' }}</td>
                    <td class="small">
                        @if($log->tracked_object)
                            <a href="{{ route('user.tracked-objects.show', $log->tracked_object) }}" class="text-decoration-none">
                                {{ $log->tracked_object->name }}
                            </a>
                        @else
                            <span class="text-muted fst-italic">{{ $log->data }}</span>
                        @endif
                    </td>
                    <td><code class="small">{{ $log->data }}</code></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted">Подій немає</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-layout>
