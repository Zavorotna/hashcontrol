<x-layout title="Чорний список">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">Ігнор-список пристроїв</h1>
            <a href="{{ route('admin.blacklisted_devices.create') }}" class="btn btn-primary btn-sm">+ Додати</a>
        </div>

        <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th class="col-hide-mobile" style="width:50px">ID</th>
                    <th>Device ID</th>
                    <th class="col-hide-mobile">Причина</th>
                    <th style="width:1px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                <tr>
                    <td class="col-hide-mobile text-muted small">{{ $device->id }}</td>
                    <td><code>{{ $device->device_id }}</code></td>
                    <td class="col-hide-mobile small text-muted">{{ $device->reason ?? '—' }}</td>
                    <td class="text-nowrap">
                        <div class="btn-actions">
                            <a href="{{ route('admin.blacklisted_devices.edit', $device) }}"
                               class="btn btn-sm btn-outline-secondary" title="Редагувати">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.blacklisted_devices.destroy', $device) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-success"
                                        onclick="return confirm('Зняти з ігнору? Пристрій знову почне оброблятись.')">
                                    <i class="bi bi-arrow-up-circle"></i> Відновити
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.blacklisted_devices.force-delete', $device->id) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Видалити назавжди з бази даних?')"
                                        title="Видалити назавжди">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Список порожній.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</x-layout>
