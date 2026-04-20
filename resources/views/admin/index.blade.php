<x-layout title="Головна — Адмін">
    <div class="container py-4">

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <h2 class="mb-3">Очікувані запити від пристроїв</h2>

        @if($pendingRequests->isEmpty())
            <p class="text-muted">Немає нових запитів.</p>
        @else
        <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Device ID</th>
                    <th>Дія</th>
                    <th>Data</th>
                    <th>Статус</th>
                    <th>Час</th>
                    <th>Операції</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingRequests as $request)
                <tr class="{{ $request->trashed() ? 'table-secondary text-muted' : '' }}">
                    <td><code>{{ $request->device_id }}</code></td>
                    <td>{{ $request->action ?? '—' }}</td>
                    <td><code>{{ $request->data }}</code></td>
                    <td>
                        @if($request->trashed())
                            <span class="badge bg-secondary">Видалено</span>
                        @else
                            <span class="badge bg-warning text-dark">Очікує</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $request->created_at->format('d.m.Y H:i') }}</td>
                    <td class="text-nowrap">
                        <div class="btn-actions">
                        @if($request->trashed())
                            <form method="POST" action="{{ route('admin.requests.restore', $request->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-info" title="Відновити">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('admin.registerDevice.form', $request->id) }}"
                               class="btn btn-sm btn-primary btn-sm">Реєстрація</a>
                            <form method="POST" action="{{ route('admin.blacklisted_devices.store') }}">
                                @csrf
                                <input type="hidden" name="device_id" value="{{ $request->device_id }}">
                                <input type="hidden" name="reason" value="Додано з панелі запитів">
                                <button type="submit" class="btn btn-sm btn-dark"
                                    onclick="return confirm('Додати {{ $request->device_id }} до ЧС?')"
                                    title="В ігнор-список">
                                    <i class="bi bi-slash-circle"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.requests.destroy', $request->id) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Видалити запит?')" title="Видалити">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</x-layout>
