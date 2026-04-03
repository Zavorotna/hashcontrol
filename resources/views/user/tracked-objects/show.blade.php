<x-layout title="{{ $trackedObject->name }}">
<div class="container py-4" style="max-width: 900px">

    @if(session('command_sent'))
        <div class="alert alert-success">{{ session('command_sent') }}</div>
    @endif
    @if(session('command_error'))
        <div class="alert alert-danger">{{ session('command_error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- ── Заголовок ────────────────────────────────────────────────────────── --}}
    @php
    $typeLabels = [
        'shop'        => '🏪 Магазин / приміщення',
        'worker'      => '👤 Працівник',
        'generator'   => '⚡ Генератор',
        'thermometer' => '🌡️ Термометр',
        'fridge'      => '🧊 Холодильник',
        'counter'     => '📊 Лічильник',
        'other'       => '📦 Інше',
    ];
    $lastLog = $recentLogs->first();
    @endphp

    @if($trackedObject->name === $trackedObject->external_id)
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span>Об'єкт зареєстрований адміністратором, але деталі ще не заповнені.</span>
            <a href="{{ route('user.tracked-objects.edit', $trackedObject) }}" class="btn btn-sm btn-warning ms-3">Заповнити</a>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">{{ $trackedObject->name }}</h2>
            <span class="badge bg-secondary">{{ $typeLabels[$trackedObject->type] ?? $trackedObject->type }}</span>
            <code class="ms-2 text-muted">{{ $trackedObject->external_id }}</code>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('user.tracked-objects.edit', $trackedObject) }}" class="btn btn-outline-secondary btn-sm">Редагувати</a>
            <a href="{{ route('user.index') }}" class="btn btn-outline-secondary btn-sm">← Назад</a>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── Ліва колонка: інфо + статистика ────────────────────────────── --}}
        <div class="col-lg-4">

            {{-- Контактні дані --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">Інформація</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted fw-normal ps-3" style="width:40%">Компанія</th>
                                <td class="pe-3">{{ $trackedObject->company->name }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal ps-3">MQTT ID</th>
                                <td class="pe-3"><code>{{ $trackedObject->external_id }}</code></td>
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

            {{-- Статистика --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">Активність</div>
                <div class="card-body">
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <div class="fs-3 fw-bold text-primary">{{ $stats['day'] }}</div>
                            <div class="small text-muted">добу</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-3 fw-bold text-primary">{{ $stats['week'] }}</div>
                            <div class="small text-muted">тиждень</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-3 fw-bold text-primary">{{ $stats['month'] }}</div>
                            <div class="small text-muted">місяць</div>
                        </div>
                    </div>

                    {{-- Остання подія (динамічно для будь-якого типу) --}}
                    @if($lastLog)
                    <hr class="my-2">
                    <p class="small text-muted mb-1">Остання подія:</p>
                    <div class="d-flex justify-content-between align-items-baseline">
                        <span class="fw-semibold">
                            @if($lastLog->action)
                                {{ $lastLog->action->title ?? $lastLog->action->name }}
                            @endif
                            <code class="ms-1">{{ $lastLog->data }}</code>
                        </span>
                        <small class="text-muted text-nowrap ms-2">
                            {{ \Carbon\Carbon::parse($lastLog->logged_at)->diffForHumans() }}
                        </small>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Пов'язані пристрої --}}
            @if($associatedDevices->isNotEmpty())
            <div class="card">
                <div class="card-header fw-semibold">Пов'язані пристрої</div>
                <ul class="list-group list-group-flush">
                    @foreach($associatedDevices as $device)
                    <li class="list-group-item py-2 px-3">
                        <div class="fw-semibold small">{{ $device->name }}</div>
                        <code class="small text-muted">{{ $device->device_id }}</code>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

        </div>

        {{-- ── Права колонка: керування + події ───────────────────────────── --}}
        <div class="col-lg-8">

            {{-- ── ВІДПРАВКА КОМАНД ─────────────────────────────────────────── --}}
            @if($associatedDevices->isNotEmpty())
            @foreach($associatedDevices as $device)
                @if($device->deviceActions->isNotEmpty())
                <div class="card mb-3">
                    <div class="card-header fw-semibold d-flex justify-content-between">
                        <span>Керування: {{ $device->name }}</span>
                        <code class="small text-muted fw-normal">{{ $device->device_id }}</code>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @foreach($device->deviceActions as $deviceAction)
                            <div class="col-md-6">
                                <div class="border rounded p-2">
                                    <div class="fw-semibold small mb-1">
                                        {{ $deviceAction->action->title ?? $deviceAction->action->name }}
                                    </div>
                                    @if($deviceAction->action->description)
                                        <div class="text-muted small mb-2">{{ $deviceAction->action->description }}</div>
                                    @endif
                                    <form method="POST" action="{{ route('user.tracked-objects.send-command', $trackedObject) }}"
                                          class="d-flex gap-1">
                                        @csrf
                                        <input type="hidden" name="device_id"   value="{{ $device->id }}">
                                        <input type="hidden" name="action_name" value="{{ $deviceAction->action->name }}">
                                        <input type="text" name="data"
                                               class="form-control form-control-sm"
                                               placeholder="{{ $deviceAction->payload ?? 'значення...' }}"
                                               value="{{ $deviceAction->payload ?? '' }}"
                                               required>
                                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                                            Надіслати
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
            @endif

            {{-- ── ОСТАННІ ПОДІЇ ────────────────────────────────────────────── --}}
            <div class="card">
                <div class="card-header fw-semibold">Останні події ({{ $recentLogs->count() }})</div>
                @if($recentLogs->isEmpty())
                    <div class="card-body text-muted">Подій ще не було.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Час</th>
                                <th>Пристрій</th>
                                <th>Дія</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentLogs as $log)
                            <tr>
                                <td class="text-muted small text-nowrap">
                                    {{ \Carbon\Carbon::parse($log->logged_at)->format('d.m.Y H:i:s') }}
                                </td>
                                <td class="small">{{ $log->device?->name ?? '—' }}</td>
                                <td class="small">{{ $log->action?->title ?? $log->action?->name ?? '—' }}</td>
                                <td><code class="small">{{ $log->data }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </div>
    </div>

</div>
</x-layout>
