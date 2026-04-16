<x-layout title="{{ $device->name }}">
<div class="container py-4" style="max-width: 860px">

    {{-- ── Alerts ───────────────────────────────────────────────────────────── --}}
    @if(session('command_sent'))
    <div class="alert alert-success d-flex justify-content-between align-items-start">
        <div>
            <strong>Команду надіслано.</strong>
            <div class="mt-1 font-monospace small text-muted">{{ session('command_sent') }}</div>
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
        </div>
        <a href="{{ route('user.index') }}?period={{ $backPeriod }}&device_view={{ $backDeviceView }}" class="btn btn-outline-secondary btn-sm">← Назад</a>
    </div>

    <div class="row g-4">

        {{-- ── Left: state + control ───────────────────────────────────────── --}}
        <div class="col-lg-5">

            {{-- ON/OFF state + quick buttons ─────────────────────────────────── --}}
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
            <div class="card mb-3">
                <div class="card-header fw-semibold">Поточний стан</div>
                <div class="card-body text-center">
                    <div class="mb-1">
                        <span class="badge bg-{{ $stateColor }} fs-5 px-4 py-2">{{ $stateLabel }}</span>
                    </div>
                    @if($lastStateLog)
                    <div class="text-muted small mt-1">
                        {{ \Carbon\Carbon::parse($lastStateLog->logged_at)->format('d.m.Y H:i:s') }}
                        <span class="ms-1">({{ \Carbon\Carbon::parse($lastStateLog->logged_at)->diffForHumans() }})</span>
                    </div>
                    @endif

                    <hr class="my-3">

                    <div class="d-flex gap-2 justify-content-center">
                        <form method="POST" action="{{ route('user.devices.send-command', $device) }}">
                            @csrf
                            <input type="hidden" name="action_name" value="{{ $defaultAction }}">
                            <input type="hidden" name="data" value="on">
                            <button type="submit"
                                    class="btn btn-success btn-lg px-4 {{ $currentState === 'on' ? 'opacity-50' : '' }}"
                                    {{ $currentState === 'on' ? 'disabled' : '' }}>
                                Ввімкнути
                            </button>
                        </form>
                        <form method="POST" action="{{ route('user.devices.send-command', $device) }}">
                            @csrf
                            <input type="hidden" name="action_name" value="{{ $defaultAction }}">
                            <input type="hidden" name="data" value="off">
                            <button type="submit"
                                    class="btn btn-danger btn-lg px-4 {{ $currentState === 'off' ? 'opacity-50' : '' }}"
                                    {{ $currentState === 'off' ? 'disabled' : '' }}>
                                Вимкнути
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            {{-- Last measurement for single-value devices ────────────────────── --}}
            @if(!$device->is_on_off && $lastMeasurement)
            <div class="card mb-3">
                <div class="card-header fw-semibold">Останнє значення</div>
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold text-primary">{{ $lastMeasurement->data }}</div>
                    <div class="text-muted small mt-1">
                        {{ \Carbon\Carbon::parse($lastMeasurement->logged_at)->format('d.m.Y H:i:s') }}
                    </div>
                </div>
            </div>
            @endif

            {{-- General command form ─────────────────────────────────────────── --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">Надіслати команду</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.devices.send-command', $device) }}">
                        @csrf

                        @if($device->deviceActions->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Дія (act)</label>
                            <select name="action_name" class="form-select form-select-sm">
                                <option value="">— без дії —</option>
                                @foreach($device->deviceActions as $da)
                                <option value="{{ $da->action->name }}">
                                    @if($da->action->title && $da->action->title !== $da->action->name)
                                        {{ $da->action->title }} ({{ $da->action->name }})
                                    @else
                                        {{ $da->action->name }}
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Дія (act)</label>
                            <input type="text" name="action_name" class="form-control form-control-sm"
                                   placeholder="назва дії або порожньо">
                        </div>
                        @endif

                        @if($device->is_on_off)
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Значення (data)</label>
                            <div class="d-flex gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="data" id="dataOn" value="on" checked>
                                    <label class="form-check-label" for="dataOn">on</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="data" id="dataOff" value="off">
                                    <label class="form-check-label" for="dataOff">off</label>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Значення (data)</label>
                            <input type="text" name="data" class="form-control form-control-sm"
                                   placeholder="значення..." required>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Надіслати на пристрій
                        </button>
                    </form>
                </div>
            </div>


        </div>

        {{-- ── Right: recent activity ──────────────────────────────────────── --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Остання активність</span>
                    <span class="badge bg-secondary">{{ $recentLogs->count() }}</span>
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

    </div>
</div>
</x-layout>
