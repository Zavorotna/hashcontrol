<x-layout title="{{ $trackedObject->name }}">
<div class="container py-4" style="max-width: 960px">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php
    $typeLabels = [
        'shop'        => 'Магазин / приміщення',
        'worker'      => 'Працівник',
        'generator'   => 'Генератор',
        'thermometer' => 'Термометр',
        'fridge'      => 'Холодильник',
        'counter'     => 'Лічильник',
        'other'       => 'Інше',
    ];
    $baseUrl = route('user.tracked-objects.show', $trackedObject);
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

    @if($trackedObject->name === $trackedObject->external_id)
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span>Об'єкт зареєстрований адміністратором, але деталі ще не заповнені.</span>
            <a href="{{ route('user.tracked-objects.edit', $trackedObject) }}" class="btn btn-sm btn-warning ms-3">Заповнити</a>
        </div>
    @endif

    {{-- ── Header ───────────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">{{ $trackedObject->name }}</h2>
            <span class="badge bg-secondary">{{ $typeLabels[$trackedObject->type] ?? $trackedObject->type }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('user.tracked-objects.edit', $trackedObject) }}" class="btn btn-outline-secondary btn-sm">Редагувати</a>
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('user.tracked-objects.index') }}" class="btn btn-outline-secondary btn-sm">← Назад</a>
            @else
                <a href="{{ route('user.companies') }}?period={{ $period }}{{ !empty($customDate) ? '&date='.$customDate : '' }}"
                   class="btn btn-outline-secondary btn-sm">← Назад</a>
            @endif
        </div>
    </div>

    {{-- ── Period selector ──────────────────────────────────────────────────── --}}
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

    <div class="row g-4">

        {{-- ── Left column ─────────────────────────────────────────────────── --}}
        <div class="col-lg-4">

            {{-- Info --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">Інформація</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted fw-normal ps-3" style="width:40%">Компанія</th>
                                <td class="pe-3">{{ $trackedObject->company->name }}</td>
                            </tr>
                            @if($trackedObject->tenant_name)
                            <tr>
                                <th class="text-muted fw-normal ps-3">Орендар</th>
                                <td class="pe-3">{{ $trackedObject->tenant_name }}</td>
                            </tr>
                            @endif
                            @if($trackedObject->email)
                            <tr>
                                <th class="text-muted fw-normal ps-3">Email</th>
                                <td class="pe-3"><a href="mailto:{{ $trackedObject->email }}">{{ $trackedObject->email }}</a></td>
                            </tr>
                            @endif
                            @if($trackedObject->phone)
                            <tr>
                                <th class="text-muted fw-normal ps-3">Телефон</th>
                                <td class="pe-3"><a href="tel:{{ $trackedObject->phone }}">{{ $trackedObject->phone }}</a></td>
                            </tr>
                            @endif
                            @if($trackedObject->address)
                            <tr>
                                <th class="text-muted fw-normal ps-3">Адреса</th>
                                <td class="pe-3">{{ $trackedObject->address }}</td>
                            </tr>
                            @endif
                            @if($currentStatus)
                            <tr>
                                <th class="text-muted fw-normal ps-3">Статус</th>
                                <td class="pe-3">
                                    @if($currentStatus['inside'])
                                        <span class="badge bg-success">всередині</span>
                                        <span class="text-muted ms-1">{{ floor($currentStatus['diff_min'] / 60) }}г {{ $currentStatus['diff_min'] % 60 }}хв</span>
                                    @else
                                        <span class="badge bg-secondary">вийшов</span>
                                        <span class="text-muted ms-1">{{ \Carbon\Carbon::parse($currentStatus['since'])->format('d.m H:i') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                    @if($trackedObject->notes)
                        <div class="px-3 pb-3">
                            <hr class="my-2">
                            <p class="small text-muted mb-1">Примітки:</p>
                            <p class="small mb-0">{{ $trackedObject->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Period summary --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">{{ $periodLabels[$period] ?? $period }}</div>
                <div class="card-body">
                    @if($hasRangePair)
                        <div class="row text-center g-2 mb-2">
                            <div class="col-6">
                                <div class="fs-3 fw-bold text-primary">{{ $periodSummary['sessions'] }}</div>
                                <div class="small text-muted">{{ $periodSummary['sessions'] === 1 ? 'сеанс' : ($periodSummary['sessions'] < 5 ? 'сеанси' : 'сеансів') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="fs-3 fw-bold text-primary">
                                    {{ $periodSummary['total_h'] }}г {{ $periodSummary['total_m'] }}хв
                                </div>
                                <div class="small text-muted">загалом</div>
                            </div>
                        </div>
                        @if($periodSummary['sessions'] > 0)
                        <div class="text-center text-muted small">
                            Середній сеанс:
                            {{ floor($periodSummary['avg_min'] / 60) }}г {{ $periodSummary['avg_min'] % 60 }}хв
                        </div>
                        @endif
                    @else
                        <div class="text-center">
                            <div class="fs-3 fw-bold text-primary">{{ $periodSummary['accesses'] }}</div>
                            <div class="small text-muted">подій за період</div>
                        </div>
                    @endif

                    {{-- All-time quick counters --}}
                    <hr class="my-3">
                    <div class="text-center mb-2">
                        <div class="small text-muted">кількість сеансів</div>
                    </div>
                    <div class="row text-center g-1">
                        <div class="col-4">
                            <div class="fw-bold">{{ $stats['day'] }}</div>
                            <div class="small text-muted">доба</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">{{ $stats['week'] }}</div>
                            <div class="small text-muted">тиждень</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">{{ $stats['month'] }}</div>
                            <div class="small text-muted">місяць</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attached devices --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                    <span>Пристрої</span>
                    <button class="btn btn-sm btn-outline-primary" type="button"
                            data-bs-toggle="collapse" data-bs-target="#addDeviceForm">
                        + Додати
                    </button>
                </div>

                {{-- Collapse: attach device form --}}
                <div class="collapse" id="addDeviceForm">
                    <div class="card-body border-bottom pb-3">
                        @if($availableDevices->isNotEmpty())
                        <form method="POST" action="{{ route('user.tracked-objects.devices.attach', $trackedObject) }}">
                            @csrf
                            <div class="d-flex gap-2">
                                <select name="device_id" class="form-select form-select-sm" required>
                                    <option value="">— виберіть пристрій —</option>
                                    @foreach($availableDevices as $dev)
                                        <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary text-nowrap">Прив'язати</button>
                            </div>
                        </form>
                        @else
                            <p class="text-muted small mb-0">Усі пристрої компанії вже прив'язані або відсутні.</p>
                        @endif
                    </div>
                </div>

                @if($trackedObject->devices->isNotEmpty())
                <ul class="list-group list-group-flush">
                    @foreach($trackedObject->devices as $device)
                    <li class="list-group-item py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">{{ $device->name }}</div>
                            @php $raw = $device->getRawOriginal('is_range_start'); @endphp
                            @if($device->is_on_off)
                                <span class="badge bg-warning text-dark ms-1 small">ON/OFF</span>
                            @elseif($raw === true || $raw === 1)
                                <span class="badge bg-light text-dark border ms-1 small">вхід</span>
                            @elseif($raw === false || $raw === 0)
                                <span class="badge bg-light text-dark border ms-1 small">вихід</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('user.tracked-objects.devices.detach', [$trackedObject, $device]) }}"
                              onsubmit="return confirm('Відв\'язати пристрій?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Відв'язати">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </li>
                    @endforeach
                </ul>
                @else
                    <div class="card-body text-muted small">Пристрої не прив'язані.</div>
                @endif
            </div>

        </div>

        {{-- ── Right column ────────────────────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- ── Sessions (range pair) ───────────────────────────────────── --}}
            @if($hasRangePair)
                @if($sessions->isEmpty())
                    <div class="card">
                        <div class="card-body text-muted">Подій за вибраний період не знайдено.</div>
                    </div>
                @else
                @php
                    $sessionsByDay = $sessions->groupBy(fn($s) => \Carbon\Carbon::parse($s['entry_at'])->format('Y-m-d'));
                @endphp
                @foreach($sessionsByDay->sortKeysDesc() as $day => $daySessions)
                @php
                    $dayLabel    = \Carbon\Carbon::parse($day)->locale('uk')->isoFormat('dddd, D MMMM YYYY');
                    $dayTotalMin = $daySessions->sum('duration_min');
                    $dayH        = floor($dayTotalMin / 60);
                    $dayM        = $dayTotalMin % 60;
                @endphp
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">{{ ucfirst($dayLabel) }}</span>
                        <span class="text-muted small">
                            {{ $daySessions->count() }} {{ $daySessions->count() === 1 ? 'сеанс' : ($daySessions->count() < 5 ? 'сеанси' : 'сеансів') }}
                            &nbsp;·&nbsp; {{ $dayH }}г {{ $dayM }}хв
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:120px">Вхід</th>
                                    <th style="width:120px">Вихід</th>
                                    <th style="width:110px">Тривалість</th>
                                    <th class="text-muted small">Пристрій входу</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($daySessions->sortByDesc('entry_at') as $session)
                                @php
                                    $h = floor($session['duration_min'] / 60);
                                    $m = $session['duration_min'] % 60;
                                @endphp
                                <tr class="{{ $session['open'] ? 'table-warning' : '' }}">
                                    <td class="fw-semibold text-nowrap">
                                        {{ \Carbon\Carbon::parse($session['entry_at'])->format('H:i:s') }}
                                    </td>
                                    <td class="text-nowrap">
                                        @if($session['exit_at'])
                                            {{ \Carbon\Carbon::parse($session['exit_at'])->format('H:i:s') }}
                                        @else
                                            <span class="badge bg-warning text-dark">всередині</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if($h > 0)<span class="fw-semibold">{{ $h }}г</span> @endif
                                        {{ $m }}хв
                                        @if($session['open'])
                                            <span class="text-muted small">(ще)</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">
                                        {{ $session['entry_device']?->name ?? '—' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            @if($daySessions->count() > 1)
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="2" class="text-end text-muted small pe-2">Разом за день:</td>
                                    <td class="fw-bold text-nowrap">{{ $dayH }}г {{ $dayM }}хв</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
                @endforeach
                @endif

            {{-- ── Access log (single reader, no pair) ────────────────────── --}}
            @else
                @if($periodLogs->isEmpty())
                    <div class="card">
                        <div class="card-body text-muted">Подій за вибраний період не знайдено.</div>
                    </div>
                @else
                @php
                    $logsByDay = $periodLogs->groupBy(fn($l) => \Carbon\Carbon::parse($l->logged_at)->format('Y-m-d'));
                @endphp
                @foreach($logsByDay->sortKeysDesc() as $day => $dayLogs)
                @php
                    $dayLabel = \Carbon\Carbon::parse($day)->locale('uk')->isoFormat('dddd, D MMMM YYYY');
                @endphp
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">{{ ucfirst($dayLabel) }}</span>
                        <span class="text-muted small">{{ $dayLogs->count() }} подій</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:130px">Час</th>
                                    <th>Пристрій</th>
                                    <th>Дія</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dayLogs->sortByDesc('logged_at') as $log)
                                <tr>
                                    <td class="text-nowrap fw-semibold">
                                        {{ \Carbon\Carbon::parse($log->logged_at)->format('H:i:s') }}
                                    </td>
                                    <td class="small">{{ $log->device?->name ?? '—' }}</td>
                                    <td class="small text-muted">{{ $log->action?->title ?? $log->action?->name ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
                @endif
            @endif

        </div>
    </div>

</div>
<script>
document.getElementById('specificDayPicker').addEventListener('change', function () {
    if (this.value) {
        window.location.href = this.dataset.baseUrl + '?period=day&date=' + this.value;
    }
});
</script>
</x-layout>
