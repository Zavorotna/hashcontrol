<x-layout title="Панель користувача">
<div class="container py-4">

    @if(isset($adminViewingAs))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>Ви переглядаєте панель як <strong>{{ $adminViewingAs->name }}</strong> ({{ $adminViewingAs->email }})</span>
            <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-dark">← До списку</a>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- ── Незареєстровані ID ───────────────────────────────────────────────── --}}
    @if($unregisteredDataIds->isNotEmpty())
    <div class="alert alert-warning">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <strong>Виявлено незареєстровані об'єкти ({{ $unregisteredDataIds->count() }})</strong><br>
                <small class="text-muted">Ці ID надходять з ваших пристроїв, але ще не мають назви.</small>
            </div>
            <a href="{{ route('user.tracked-objects.index') }}" class="btn btn-sm btn-warning">Керувати об'єктами</a>
        </div>
        <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
            <select id="unregisteredSelect" class="form-select form-select-sm" style="max-width:260px">
                <option value="">— Виберіть ID —</option>
                @foreach($unregisteredDataIds as $dataId)
                    <option value="{{ $dataId }}">{{ $dataId }}</option>
                @endforeach
            </select>
            <a id="registerBtn" href="{{ route('user.tracked-objects.create') }}"
               class="btn btn-sm btn-primary disabled">Зареєструвати</a>
        </div>
    </div>
    @endif

    {{-- ── Компанії та об'єкти ─────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Ваші компанії та об'єкти</h2>
        <a href="{{ route('user.tracked-objects.create') }}" class="btn btn-outline-primary btn-sm">+ Додати об'єкт</a>
    </div>

    @php
    $typeLabels = [
        'shop'        => '🏪 Магазини / приміщення',
        'worker'      => '👤 Працівники',
        'generator'   => '⚡ Генератори',
        'thermometer' => '🌡️ Термометри',
        'fridge'      => '🧊 Холодильники',
        'counter'     => '📊 Лічильники',
        'other'       => '📦 Інше',
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
                {{-- Групуємо по типу — динамічно для будь-яких типів --}}
                @foreach($companyObjects->groupBy('type') as $type => $group)
                <h6 class="mt-3 mb-2">{{ $typeLabels[$type] ?? ('📦 ' . ucfirst($type)) }}</h6>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Назва</th>
                                <th>ID (MQTT)</th>
                                <th class="text-center" style="width:70px">Добу</th>
                                <th class="text-center" style="width:80px">Тиждень</th>
                                <th class="text-center" style="width:80px">Місяць</th>
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
                                        <br><a href="{{ route('user.tracked-objects.edit', $obj) }}" class="small text-warning">⚠ Заповнити дані</a>
                                    @elseif($obj->tenant_name)
                                        <br><small class="text-muted">{{ $obj->tenant_name }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $obj->external_id }}</code></td>
                                <td class="text-center">{{ $st['day'] ?? 0 }}</td>
                                <td class="text-center">{{ $st['week'] ?? 0 }}</td>
                                <td class="text-center">{{ $st['month'] ?? 0 }}</td>
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

    {{-- ── Пристрої ────────────────────────────────────────────────────────── --}}
    <h2 class="mb-3 mt-2">Ваші пристрої</h2>
    <div class="row g-3 mb-4">
        @forelse($devices as $device)
        <div class="col-md-6">
            <div class="card">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <strong>{{ $device->name }}</strong>
                        <code class="text-muted small">{{ $device->device_id }}</code>
                    </div>
                    @if($device->deviceActions->isNotEmpty())
                    <div class="mt-1 d-flex flex-wrap gap-1">
                        @foreach($device->deviceActions as $da)
                            <span class="badge bg-light text-dark border small">
                                {{ $da->action->title ?? $da->action->name }}
                                @if($da->payload) <span class="text-muted">({{ $da->payload }})</span> @endif
                            </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col"><p class="text-muted">Немає призначених пристроїв.</p></div>
        @endforelse
    </div>

    {{-- ── Останні події ────────────────────────────────────────────────────── --}}
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
<script>
    const select  = document.getElementById('unregisteredSelect');
    const btn     = document.getElementById('registerBtn');
    const baseUrl = "{{ route('user.tracked-objects.create') }}";
    if (select) {
        select.addEventListener('change', function () {
            if (this.value) {
                btn.href = baseUrl + '?external_id=' + encodeURIComponent(this.value);
                btn.classList.remove('disabled');
            } else {
                btn.href = baseUrl;
                btn.classList.add('disabled');
            }
        });
    }
</script>
</x-layout>
