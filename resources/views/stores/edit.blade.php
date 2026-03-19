<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('stores.index') }}" class="hover:text-blue-600">Магазини</a><span>/</span>
            <a href="{{ route('stores.show', $store) }}" class="hover:text-blue-600">{{ $store->name }}</a><span>/</span>
            <span class="text-gray-900">Редагувати</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Редагувати магазин</h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('stores.update', $store) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Топік *</label>
                        <select name="topic_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}" {{ old('topic_id', $store->topic_id) == $topic->id ? 'selected' : '' }}>{{ $topic->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">MQTT Device ID *</label>
                        <input type="text" name="mqtt_device_id" value="{{ old('mqtt_device_id', $store->mqtt_device_id) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Назва *</label>
                    <input type="text" name="name" value="{{ old('name', $store->name) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Відповідальний</label>
                        <input type="text" name="employee_name" value="{{ old('employee_name', $store->employee_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Місцезнаходження</label>
                        <input type="text" name="location" value="{{ old('location', $store->location) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $store->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-blue-600">
                    <label for="is_active" class="text-sm text-gray-700">Активний</label>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Зберегти зміни</button>
                    <a href="{{ route('stores.show', $store) }}" class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Скасувати</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
