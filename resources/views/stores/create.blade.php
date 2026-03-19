<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('stores.index') }}" class="hover:text-blue-600">Магазини</a><span>/</span>
            <span class="text-gray-900">Новий магазин</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Додати магазин</h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('stores.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Топік *</label>
                        <select name="topic_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('topic_id') border-red-400 @enderror">
                            <option value="">Оберіть топік...</option>
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}" {{ old('topic_id') == $topic->id ? 'selected' : '' }}>{{ $topic->name }} ({{ $topic->slug }})</option>
                            @endforeach
                        </select>
                        @error('topic_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">MQTT Device ID *</label>
                        <input type="text" name="mqtt_device_id" value="{{ old('mqtt_device_id') }}" required placeholder="STORE_01"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono @error('mqtt_device_id') border-red-400 @enderror">
                        @error('mqtt_device_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Назва *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Магазин №1"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Відповідальний</label>
                        <input type="text" name="employee_name" value="{{ old('employee_name') }}" placeholder="Іванова Олена"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Місцезнаходження</label>
                        <input type="text" name="location" value="{{ old('location') }}" placeholder="Корпус A"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', 1) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-blue-600">
                    <label for="is_active" class="text-sm text-gray-700">Активний</label>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">Зберегти</button>
                    <a href="{{ route('stores.index') }}" class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition">Скасувати</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
