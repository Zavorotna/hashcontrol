<x-layout title="Пристрої">
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Пристрої</h1>
        <a href="{{ route('admin.devices.create') }}" class="btn btn-primary">+ Додати пристрій</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- ── Pending device requests ─────────────────────────────────────────── --}}
    @if($pendingRequests->isNotEmpty())
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning bg-opacity-10 fw-semibold">
            Очікують реєстрації ({{ $pendingRequests->count() }})
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Device ID</th>
                        <th>Дія</th>
                        <th>Data</th>
                        <th>Час</th>
                        <th style="width:200px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingRequests as $req)
                    <tr class="{{ $req->trashed() ? 'text-muted' : '' }}">
                        <td><code>{{ $req->device_id }}</code></td>
                        <td class="small">{{ $req->action ?? '—' }}</td>
                        <td><code class="small">{{ $req->data }}</code></td>
                        <td class="small text-muted text-nowrap">{{ $req->updated_at->format('d.m H:i') }}</td>
                        <td class="text-nowrap">
                            @if($req->trashed())
                                <div class="btn-actions">
                                    <form method="POST" action="{{ route('admin.requests.restore', $req->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-info" title="Відновити">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="btn-actions">
                                    <a href="{{ route('admin.registerDevice.form', $req->id) }}"
                                       class="btn btn-sm btn-primary">Реєстрація</a>
                                    <form method="POST" action="{{ route('admin.blacklisted_devices.store') }}"
                                          onsubmit="return confirm('Додати {{ $req->device_id }} до ігнор-списку?')">
                                        @csrf
                                        <input type="hidden" name="device_id" value="{{ $req->device_id }}">
                                        <input type="hidden" name="reason" value="Додано з панелі запитів">
                                        <button type="submit" class="btn btn-sm btn-dark" title="В ігнор">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.requests.destroy', $req->id) }}"
                                          onsubmit="return confirm('Видалити запит?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Видалити">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @php
        $active  = $devices->whereNull('deleted_at');
        $deleted = $devices->whereNotNull('deleted_at');
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- BLOCK 2: Registered devices grouped by owner → company                 --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <h4 class="mb-3">Зареєстровані пристрої ({{ $active->count() }})</h4>

    @php
        // Group active devices by user (owner), then by company within each owner
        $grouped = $active->groupBy(fn($d) => $d->user?->name ?? '— Без власника');
    @endphp

    @forelse($grouped as $ownerName => $ownerDevices)
    <div class="mb-4">
        <h6 class="text-muted fw-semibold mb-2 text-uppercase" style="font-size:.75rem;letter-spacing:.05em">
            {{ $ownerName }}
        </h6>

        @foreach($ownerDevices->groupBy(fn($d) => $d->company?->name ?? '— Без компанії') as $companyName => $companyDevices)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold">{{ $companyName }}</span>
                <span class="badge bg-secondary">{{ $companyDevices->count() }} {{ $companyDevices->count() === 1 ? 'пристрій' : ($companyDevices->count() < 5 ? 'пристрої' : 'пристроїв') }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:110px">Device ID</th>
                            <th>Назва</th>
                            <th style="width:160px">Тип</th>
                            <th>Дії пристрою</th>
                            <th class="text-muted small" style="width:120px">Додано</th>
                            <th style="width:150px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companyDevices as $device)
                        @php
                            $isBlacklisted = $blacklistedDevices->get($device->device_id);
                            $rangeRaw = $device->getRawOriginal('is_range_start');
                        @endphp
                        <tr>
                            <td>
                                <code class="small">{{ $device->device_id }}</code>
                                @if($isBlacklisted && !$isBlacklisted->trashed())
                                    <span class="badge bg-danger ms-1" title="У чорному списку">ЧС</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $device->name }}</td>
                            <td>
                                @if($device->is_on_off)
                                    <span class="badge bg-warning text-dark">ON/OFF</span>
                                @elseif($rangeRaw === true || $rangeRaw === 1)
                                    <span class="badge bg-success">▶ вхід</span>
                                @elseif($rangeRaw === false || $rangeRaw === 0)
                                    <span class="badge bg-secondary">■ вихід</span>
                                @else
                                    <span class="badge bg-light text-dark border">● одиничний</span>
                                @endif
                            </td>
                            <td>
                                @if($device->deviceActions->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($device->deviceActions as $da)
                                        <span class="badge bg-light text-dark border small d-flex align-items-center gap-1">
                                            {{ $da->action->title ?? $da->action->name }}
                                            @if($da->payload)
                                                <span class="text-muted">({{ $da->payload }})</span>
                                            @endif
                                            <form method="POST"
                                                  action="{{ route('admin.devices.actions.destroy', ['device' => $device->id, 'deviceAction' => $da->id]) }}"
                                                  class="d-inline m-0 p-0"
                                                  onsubmit="return confirm('Видалити дію?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="btn-close btn-close p-0 ms-1"
                                                        style="font-size:.55rem"
                                                        title="Видалити дію"></button>
                                            </form>
                                        </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="small text-muted text-nowrap">
                                {{ \Carbon\Carbon::parse($device->created_at)->format('d.m.Y') }}
                            </td>
                            <td class="text-nowrap">
                                <div class="btn-actions justify-content-end">
                                    <a href="{{ route('admin.devices.edit', $device) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Редагувати">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    @if($isBlacklisted && !$isBlacklisted->trashed())
                                        <form method="POST" action="{{ route('admin.blacklisted_devices.destroy', $isBlacklisted) }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-success" title="Зняти з ігнору">
                                                <i class="bi bi-arrow-up-circle"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.blacklisted_devices.store') }}">
                                            @csrf
                                            <input type="hidden" name="device_id" value="{{ $device->device_id }}">
                                            <input type="hidden" name="reason" value="Manually blacklisted">
                                            <button class="btn btn-sm btn-outline-dark" title="Додати в ігнор">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.devices.destroy', $device) }}"
                                          onsubmit="return confirm('Видалити пристрій?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Видалити">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
    @empty
        <p class="text-muted">Зареєстрованих пристроїв немає.</p>
    @endforelse

    {{-- ── Deleted devices ──────────────────────────────────────────────────── --}}
    @if($deleted->isNotEmpty())
    <div class="mt-4">
        <h6 class="text-muted fw-semibold mb-2 text-uppercase" style="font-size:.75rem;letter-spacing:.05em">
            Видалені ({{ $deleted->count() }})
        </h6>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Device ID</th>
                            <th>Назва</th>
                            <th>Власник</th>
                            <th>Компанія</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deleted as $device)
                        <tr class="text-muted">
                            <td><code class="small">{{ $device->device_id }}</code></td>
                            <td class="text-decoration-line-through">{{ $device->name }}</td>
                            <td class="small">{{ $device->user?->name ?? '—' }}</td>
                            <td class="small">{{ $device->company?->name ?? '—' }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.devices.restore', $device->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">Відновити</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
</x-layout>
