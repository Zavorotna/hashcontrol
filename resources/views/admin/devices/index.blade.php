<x-layout title="Пристрої">
    <div class="container">
        <h1>Пристрої</h1>
        <a href="{{ route('admin.devices.create') }}" class="btn btn-primary mb-3">Створити пристрій</a>
        <table class="table">
            <thead>
                <tr>
                    <th>Device ID</th>
                    <th>Назва</th>
                    <th class="text-center">Роль</th>
                    <th>Користувач</th>
                    <th>Компанія</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($devices as $device)
                <tr>
                    <td><code>{{ $device->device_id }}</code></td>
                    <td>{{ $device->name }}</td>
                    <td class="text-center">
                        @if(is_null($device->is_range_start))
                            <span class="badge bg-light text-dark border">● одиничний</span>
                        @elseif($device->is_range_start)
                            <span class="badge bg-success">▶ початок</span>
                        @else
                            <span class="badge bg-secondary">■ кінець</span>
                        @endif
                    </td>
                    <td>{{ $device->user ? $device->user->name : '—' }}</td>
                    <td>{{ $device->company ? $device->company->name : '—' }}</td>
                    <td>
                        <a href="{{ route('admin.devices.edit', $device) }}" class="btn btn-sm btn-warning">Редагувати</a>

                        @php
                            $blacklisted = \App\Models\BlacklistedDevice::withTrashed()->where('device_id', $device->device_id)->first();
                        @endphp

                        @if($blacklisted)
                            @if($blacklisted->trashed())
                                <form method="POST" action="{{ route('admin.blacklisted_devices.restore', $blacklisted->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-info">Відновити в ЧС</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.blacklisted_devices.destroy', $blacklisted) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Вилучити з ЧС</button>
                                </form>
                            @endif
                        @else
                            <form method="POST" action="{{ route('admin.blacklisted_devices.store') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="device_id" value="{{ $device->device_id }}" />
                                <input type="hidden" name="reason" value="Автоматично з пристрою" />
                                <button type="submit" class="btn btn-sm btn-dark">Додати в ЧС</button>
                            </form>
                        @endif

                        @if($device->trashed())
                        <form method="POST" action="{{ route('admin.devices.restore', $device->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-info">Відновити</button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('admin.devices.destroy', $device) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Впевнені?')">Видалити</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @if($device->deviceActions->isNotEmpty())
                <tr>
                    <td colspan="6">
                        <strong>Призначені дії:</strong>
                        <ul>
                            @foreach($device->deviceActions as $action)
                            <li>
                                {{ $action->action->title ?? $action->action->name }}
                                (payload: {{ $action->payload ?? '-' }})
                                <form method="POST" action="{{ route('admin.devices.actions.destroy', ['device' => $device->id, 'deviceAction' => $action->id]) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Видалити</button>
                                </form>
                            </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>