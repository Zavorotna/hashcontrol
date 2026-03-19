<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-gray-900">Редагувати сесію генератора</h1></x-slot>
    <div class="py-6"><div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="POST" action="{{ route('sessions.update', $session) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Топік *</label>
                <select name="topic_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach($topics as $topic)
                        <option value="{{ $topic->id }}" {{ old('topic_id', $session->topic_id) == $topic->id ? 'selected' : '' }}>
                            {{ $topic->name }} ({{ $topic->fuel_rate_per_hour }} л/год)
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Початок *</label>
                    <input type="datetime-local" name="started_at" required
                           value="{{ old('started_at', $session->started_at->format('Y-m-d\TH:i')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @error('started_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Кінець</label>
                    <input type="datetime-local" name="stopped_at"
                           value="{{ old('stopped_at', $session->stopped_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @error('stopped_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            @if($session->fuel_consumed_total)
            <div class="bg-orange-50 border border-orange-200 rounded-lg px-4 py-3 text-sm text-orange-700">
                Поточна витрата: <span class="font-bold">{{ $session->fuel_consumed_total }} л</span> — буде перерахована при збереженні
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Примітки</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes', $session->notes) }}</textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Зберегти зміни</button>
                <a href="{{ route('sessions.index') }}" class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Скасувати</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
