<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Додати пристрій</h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <form method="POST" action="{{ route('devices.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                @csrf

                {{-- Топік + Тип події --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Топік *</label>
                        <select name="topic_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('topic_id') border-red-400 @enderror">
                            <option value="">Оберіть топік...</option>
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}" {{ old('topic_id') == $topic->id ? 'selected' : '' }}>
                                    {{ $topic->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('topic_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Тип події *</label>
                        <select name="type" required onchange="handleTypeChange(this.value)"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('type') border-red-400 @enderror">
                            <option value="">Оберіть тип...</option>
                            @foreach($eventTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- MQTT Device ID --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">MQTT Device ID *</label>
                    <input type="text" name="mqtt_device_id" value="{{ old('mqtt_device_id') }}"
                           required placeholder="STORE_01_R1"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono @error('mqtt_device_id') border-red-400 @enderror">
                    @error('mqtt_device_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Магазин (тільки для рідерів) --}}
                <div id="storeRow" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Магазин</label>
                    <select name="store_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">— без магазину —</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->name }} — {{ $store->topic->slug }}
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
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('session_type') border-red-400 @enderror">
                        @foreach($sessionTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('session_type') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('session_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Метрика --}}
                <div id="metricRow" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Витрата за годину
                            <span class="text-xs text-gray-400 font-normal">— необов'язково</span>
                        </label>
                        <input type="number" name="metric_rate_per_hour" value="{{ old('metric_rate_per_hour') }}"
                               step="0.01" min="0" placeholder="5.00"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm @error('metric_rate_per_hour') border-red-400 @enderror">
                        @error('metric_rate_per_hour')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Одиниця</label>
                        <select name="metric_unit"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— без метрики —</option>
                            @foreach($metricUnits as $value => $label)
                                <option value="{{ $value }}" {{ old('metric_unit') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Назва метрики</label>
                        <input type="text" name="metric_label" value="{{ old('metric_label') }}"
                               placeholder="Витрата пального"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Назва + Активний --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Назва</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           placeholder="Рідер вхід..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', 1) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-gray-300 text-blue-600">
                    <label for="is_active" class="text-sm text-gray-700">Активний</label>
                </div>

                <div class="flex items-center gap-3 pt-2">
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
        // Показати магазин тільки для рідерів
        document.getElementById('storeRow').classList.toggle('hidden', !readerTypes.includes(type));

        // Автоматично підставити session_type
        const sessionMap = {
            'reader_open':   'store_open',
            'reader_close':  'store_open',
            'generator_on':  'generator',
            'generator_off': 'generator',
            'generic_on':    'generic',
            'generic_off':   'generic',
        };
        if (sessionMap[type]) {
            document.querySelector('[name=session_type]').value = sessionMap[type];
        }
    }

    // Ініціалізація при завантаженні
    handleTypeChange('{{ old('type') }}');
    </script>
</x-app-layout>