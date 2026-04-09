<x-layout title="Чорний список">
    <div class="container">
        <h1>Чорний список пристроїв</h1>
        <a href="{{ route('admin.blacklisted_devices.create') }}" class="btn btn-primary mb-3">Додати в чорний список</a>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Device ID</th>
                    <th>Причина</th>
                    <th>Статус</th>
                    <th>Дії</th>
                </tr>
            </thead>
            <tbody>
                @foreach($devices as $device)
                <tr class="{{ $device->trashed() ? 'table-secondary' : '' }}">
                    <td>{{ $device->id }}</td>
                    <td><code>{{ $device->device_id }}</code></td>
                    <td>{{ $device->reason ?? '—' }}</td>
                    <td>{!! $device->trashed() ? '<span class="badge bg-warning">Видалено</span>' : '<span class="badge bg-danger">Чорний</span>' !!}</td>
                    <td>
                        @if($device->trashed())
                            <form method="POST" action="{{ route('admin.blacklisted_devices.restore', $device->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-info">Відновити</button>
                            </form>
                        @else
                            <a href="{{ route('admin.blacklisted_devices.edit', $device) }}" class="btn btn-sm btn-warning">Редагувати</a>
                            <form method="POST" action="{{ route('admin.blacklisted_devices.destroy', $device) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Впевнені?')">Видалити</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
