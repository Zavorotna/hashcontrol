<x-layout title="Події">
<div class="container py-4">

    {{-- ── Section navigation ────────────────────────────────────────────── --}}
    @php
        $devUrl = isset($viewingAs) ? route('admin.users.dashboard', $viewingAs) : route('user.index');
        $coUrl  = isset($viewingAs) ? route('admin.users.dashboard', $viewingAs).'?section=companies' : route('user.companies');
        $evUrl  = isset($viewingAs) ? route('admin.users.dashboard', $viewingAs).'?section=events' : route('user.events');
    @endphp
    @if(isset($viewingAs))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>Ви переглядаєте панель як <strong>{{ $viewingAs->name }}</strong> ({{ $viewingAs->email }})</span>
            <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-dark">← До списку</a>
        </div>
    @endif
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ $devUrl }}" class="btn btn-sm btn-outline-secondary">Пристрої</a>
        <a href="{{ $coUrl }}" class="btn btn-sm btn-outline-secondary">Компанії та об'єкти</a>
        <a href="{{ $evUrl }}" class="btn btn-sm btn-primary">Події</a>
    </div>

    {{-- ── Period selector ───────────────────────────────────────────────── --}}
    @php $baseUrl = route('user.events'); @endphp
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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Події</h2>
        <span class="text-muted small">Всього: {{ $logs->total() }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead class="table-light">
                <tr>
                    <th>Час</th>
                    <th>Пристрій</th>
                    <th>Дія</th>
                    <th>Об'єкт</th>
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
                            <span class="text-muted fst-italic">—</span>
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

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $logs->appends(['period' => $period, 'date' => $customDate])->links('pagination::bootstrap-5') }}
    </div>
    @endif

</div>
</x-layout>
