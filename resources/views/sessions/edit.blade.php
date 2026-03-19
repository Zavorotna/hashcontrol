<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Редагувати сесію #{{ $session->id }}</h1>
            <a href="{{ route('sessions.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                ← Назад
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('sessions.update', $session) }}"
                  class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                @csrf @method('PUT')

                <div class="text-sm text-gray-500 bg-gray-50 rounded-lg p-3">
                    <span class="font-medium">{{ $session->type_label }}</span> —
                    {{ $session->subject?->name ?? '—' }}
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Початок <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" name="started_at"
                           value="{{ old('started_at', $session->started_at->format('Y-m-d\TH:i')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('started_at') border-red-400 @enderror">
                    @error('started_at')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Кінець</label>
                    <input type="datetime-local" name="ended_at"
                           value="{{ old('ended_at', $session->ended_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('ended_at') border-red-400 @enderror">
                    @error('ended_at')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">Залиште порожнім якщо сесія ще активна</p>
                </div>

                @if($session->metric_rate_per_hour)
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-600">
                    Метрика буде перерахована автоматично після збереження.<br>
                    Витрата: {{ $session->metric_rate_per_hour }} {{ $session->metric_unit }}/год
                </div>
                @endif

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                        Зберегти
                    </button>
                    <a href="{{ route('sessions.index') }}"
                       class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                        Скасувати
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>