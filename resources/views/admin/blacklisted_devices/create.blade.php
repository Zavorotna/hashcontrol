<x-layout title="Додати чорний список">
    <div class="container">
        <h1>Додати пристрій у чорний список</h1>

        <form method="POST" action="{{ route('admin.blacklisted_devices.store') }}">
            @csrf
            <div class="mb-3">
                <label>Device ID</label>
                <input type="text" name="device_id" class="form-control" value="{{ old('device_id') }}" required>
            </div>
            <div class="mb-3">
                <label>Причина</label>
                <textarea name="reason" class="form-control">{{ old('reason') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Додати</button>
            <a href="{{ route('admin.blacklisted_devices') }}" class="btn btn-secondary">Назад</a>
        </form>
    </div>
</x-layout>
