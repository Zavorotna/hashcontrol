<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-gray-900">Додати сесію генератора вручну</h1></x-slot>
    <div class="py-6"><div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="POST" action="{{ route('sessions.index') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Топік *</label>
                <select name="topic_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Оберіть...</option>
                    @foreach($topics as $topic)
                        <option value="{{ $topic->id }}" {{ old('topic_id') == $topic->id ? 'selected' : '' }}>{{ $topic->name }} ({{ $topic->fuel_rate_per_hour }} л/год)</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Початок *</label>
                    <input type="datetime-local" name="started_at" value="{{ old('started_at') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Кінець</label>
                    <input type="datetime-local" name="stopped_at" value="{{ old('stopped_at') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3 text-sm text-yellow-700">
                💡 Витрата пального розраховується автоматично
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Примітки</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600">Зберегти</button>
                <a href="{{ route('sessions.index') }}" class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Скасувати</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
