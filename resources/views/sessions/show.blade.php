<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Сесія #{{ $session->id }}</h1>
            <a href="{{ route('sessions.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                ← Назад
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">

                <div class="flex items-center justify-between">
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        {{ $session->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $session->is_active ? '● Активна' : '● Завершена' }}
                    </span>
                    <span class="text-sm text-gray-500">{{ $session->type_label }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Об'єкт</p>
                        <p class="font-medium">{{ $session->subject?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Топік</p>
                        <p class="font-medium">{{ $session->topic?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Пристрій</p>
                        <p class="font-medium">{{ $session->device?->name ?? $session->device?->mqtt_device_id ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Тривалість</p>
                        <p class="font-medium">{{ $session->duration_human }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Початок</p>
                        <p class="font-medium">{{ $session->started_at->format('d.m.Y H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Кінець</p>
                        <p class="font-medium">{{ $session->ended_at?->format('d.m.Y H:i:s') ?? '—' }}</p>
                    </div>
                    @if($session->metric_consumed)
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">
                            {{ $session->metric_label ?? 'Метрика' }}
                        </p>
                        <p class="font-medium text-orange-600">{{ $session->metric_consumed_label }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Витрата/год</p>
                        <p class="font-medium">{{ $session->metric_rate_per_hour }} {{ $session->metric_unit }}</p>
                    </div>
                    @endif
                </div>

                @if($session->is_active && $session->live_metric)
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
                    <p class="text-xs text-orange-600 uppercase tracking-wide mb-1">Зараз витрачено</p>
                    <p class="text-2xl font-bold text-orange-700">
                        {{ $session->live_metric }} {{ $session->metric_unit }}
                    </p>
                </div>
                @endif

                @auth
                    @if(auth()->user()->isAdmin())
                    <div class="flex gap-3 pt-2">
                        <a href="{{ route('sessions.edit', $session) }}"
                           class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                            Редагувати
                        </a>
                        <form method="POST" action="{{ route('sessions.destroy', $session) }}"
                              onsubmit="return confirm('Видалити сесію?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2 border border-red-200 text-red-600 rounded-lg text-sm hover:bg-red-50">
                                Видалити
                            </button>
                        </form>
                    </div>
                    @endif
                @endauth
            </div>
        </div>
    </div>
</x-app-layout>