<x-layout title="Компанії та об'єкти">
<div class="container py-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(isset($viewingAs))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>Ви переглядаєте панель як <strong>{{ $viewingAs->name }}</strong> ({{ $viewingAs->email }})</span>
            <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-dark">← До списку</a>
        </div>
    @endif

    {{-- ── Period selector ───────────────────────────────────────────────── --}}
    @php $baseUrl = route('user.companies'); @endphp
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

    {{-- ── Companies and objects ──────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Ваші компанії та об'єкти</h2>
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('user.tracked-objects.create') }}" class="btn btn-outline-primary btn-sm">+ Додати об'єкт</a>
        @endif
    </div>

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
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('user.tracked-objects.create') }}">Додати</a>
                    @endif
                </p>
            @else
                @foreach($companyObjects->groupBy('type') as $type => $group)
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
                <h6 class="mt-3 mb-2">{{ $typeLabels[$type] ?? ucfirst($type) }}</h6>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Назва</th>
                                <th class="text-center" style="width:100px">За {{ $periodLabels[$period] ?? $period }}</th>
                                <th>Остання подія</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group as $obj)
                            @php $st = $objectStats[$obj->id] ?? [] @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('user.tracked-objects.show', $obj) }}?period={{ $period }}{{ !empty($customDate) ? '&date='.$customDate : '' }}"
                                       class="fw-semibold text-decoration-none">
                                        {{ $obj->name }}
                                    </a>
                                    @if($obj->name === $obj->external_id)
                                        <br><a href="{{ route('user.tracked-objects.edit', $obj) }}" class="small text-warning">Заповнити дані</a>
                                    @elseif($obj->tenant_name)
                                        <br><small class="text-muted">{{ $obj->tenant_name }}</small>
                                    @endif
                                </td>
                                <td class="text-center fw-semibold">{{ $st['period'] ?? 0 }}</td>
                                <td class="small">
                                    @if(!empty($st['last_at']))
                                        <span class="text-muted">{{ \Carbon\Carbon::parse($st['last_at'])->format('d.m H:i') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('user.tracked-objects.show', $obj) }}?period={{ $period }}{{ !empty($customDate) ? '&date='.$customDate : '' }}"
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

    {{-- ── Unregistered data IDs (admin only) ───────────────────────────────── --}}
    @if((isset($viewingAs) || auth()->user()->role === 'admin') && $unregisteredDataIds->isNotEmpty())
    <div class="alert alert-warning mt-2">
        <strong>Незареєстровані ID:</strong>
        @foreach($unregisteredDataIds as $uid)
            <a href="{{ route('user.tracked-objects.create', ['external_id' => $uid]) }}" class="ms-1 text-decoration-none">
                <code>{{ $uid }}</code>
            </a>
        @endforeach
        <div class="small mt-1 text-muted">Ці значення надходять з пристроїв, але не пов'язані з жодним об'єктом.</div>
    </div>
    @endif

</div>
</x-layout>
