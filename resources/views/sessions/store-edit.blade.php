<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-gray-900">Редагувати сесію магазину</h1></x-slot>
    <div class="py-6"><div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-600">
            Магазин: <span class="font-medium">{{ $session->store->name }}</span>
        </div>
        <form method="POST" action="{{ route('sessions.update', $session) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Відкрито *</label>
                    <input type="datetime-local" name="opened_at" required
                           value="{{ old('opened_at', $session->opened_at->format('Y-m-d\TH:i')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @error('opened_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Закрито</label>
                    <input type="datetime-local" name="closed_at"
                           value="{{ old('closed_at', $session->closed_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @error('closed_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Примітки</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes', $session->notes) }}</textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Зберегти</button>
                <a href="{{ route('sessions.index') }}" class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Скасувати</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
