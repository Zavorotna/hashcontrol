<x-layout title="Редагувати чорний список">
    <div class="container">
        <h1>Редагувати ч/с</h1>

        <form method="POST" action="{{ route('admin.blacklisted_devices.update', $blacklistedDevice) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label>Device ID</label>
                <input type="text" name="device_id" class="form-control" value="{{ $blacklistedDevice->device_id }}" required>
            </div>
            <div class="mb-3">
                <label>Причина</label>
                <textarea name="reason" class="form-control">{{ $blacklistedDevice->reason }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Зберегти</button>
            <a href="{{ route('admin.blacklisted_devices') }}" class="btn btn-secondary">Назад</a>
        </form>
    </div>
</x-layout>
