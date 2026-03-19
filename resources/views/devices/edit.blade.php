<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Редагувати пристрій</h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('devices.update', $device) }}"
                  class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                @csrf @method('PUT')

                {{-- Топік + Тип події --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Топік *</label>
                        <select name="topic_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}"
                                    {{ old('topic_id', $device->topic_id) == $topic->id ? 'selected' : '' }}>
                                    {{ $topic->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Тип події *</label>
                        <select name="type" required onchange="handleTypeChange(this.value)"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach($eventTypes as $value => $label)
                                <option value="{{ $value }}"
                                    {{ old('type', $device->type) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- MQTT Device ID --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">MQTT Device ID *</label>
                    <input type="text" name="mqtt_device_id"
                           value="{{ old('mqtt_device_id', $device->mqtt_device_id) }}"
                           required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                </div>

                {{-- Магазин --}}
                <div id="storeRow" class="{{ !$device->isStoreDevice() ? 'hidden' : '' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Магазин</label>
                    <select name="store_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">— без магазину —</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}"
                                {{ old('store_id', $device->store_id) == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <hr class="border-gray-100">

                {{-- Тип сесії --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Тип сесії *
                        <span class="text-xs text-gray-400 font-normal ml-1">— що буде записуватись при спрацюванні</span>
                    </label>
                    <select name="session_type" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        @foreach($sessionTypes as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('session_type', $device->session_type) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Метрика --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Витрата за годину</label>
                        <input type="number" name="metric_rate_per_hour"
                               value="{{ old('metric_rate_per_hour', $device->metric_rate_per_hour) }}"
                               step="0.01" min="0" placeholder="5.00"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Одиниця</label>
                        <select name="metric_unit"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— без метрики —</option>
                            @foreach($metricUnits as $value => $label)
                                <option value="{{ $value }}"
                                    {{ old('metric_unit', $device->metric_unit) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Назва метрики</label>
                        <input type="text" name="metric_label"
                               value="{{ old('metric_label', $device->metric_label) }}"
                               placeholder="Витрата пального"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Назва + Активний --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Назва</label>
                    <input type="text" name="name"
                           value="{{ old('name', $device->name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $device->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-gray-300 text-blue-600">
                    <label class="text-sm text-gray-700">Активний</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        Зберегти
                    </button>
                    <a href="{{ route('devices.index') }}"
                       class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                        Скасувати
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    const readerTypes = ['reader_open', 'reader_close'];

    function handleTypeChange(type) {
        document.getElementById('storeRow').classList.toggle('hidden', !readerTypes.includes(type));
    }
    </script>
</x-app-layout>