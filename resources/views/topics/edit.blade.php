<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Редагувати топік</h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('topics.update', $topic) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Назва *</label>
                        <input type="text" name="name" value="{{ old('name', $topic->name) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">MQTT Slug *</label>
                        <input type="text" name="slug" value="{{ old('slug', $topic->slug) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                        <p class="text-xs text-orange-500 mt-1">⚠ Зміна slug потребує перезапуску mqtt:listen</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Опис</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('description', $topic->description) }}</textarea>
                </div>
                <div class="border border-gray-200 rounded-xl p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="has_generator" value="0">
                        <input type="checkbox" name="has_generator" value="1" id="has_generator"
                               {{ old('has_generator', $topic->has_generator) ? 'checked' : '' }}
                               onchange="document.getElementById('fuelRow').classList.toggle('hidden', !this.checked)"
                               class="w-4 h-4 rounded border-gray-300 text-yellow-500">
                        <label for="has_generator" class="text-sm font-medium text-gray-700">⚡ Є генератор</label>
                    </div>
                    <div id="fuelRow" class="{{ old('has_generator', $topic->has_generator) ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Норма витрати (л/год)</label>
                        <input type="number" name="fuel_rate_per_hour" value="{{ old('fuel_rate_per_hour', $topic->fuel_rate_per_hour) }}"
                               min="0" max="999" step="0.01" class="w-40 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $topic->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-gray-300 text-blue-600">
                    <label for="is_active" class="text-sm text-gray-700">Активний</label>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Зберегти зміни</button>
                    <a href="{{ route('topics.index') }}" class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Скасувати</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
