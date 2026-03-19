<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                    <a href="{{ route('stores.index') }}" class="hover:text-blue-600">Магазини</a>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">{{ $store->name }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $store->name }}</h1>
                    @php $open = $store->isOpen(); @endphp
                    <span class="flex items-center gap-1.5 text-sm font-semibold px-3 py-1 rounded-full {{ $open ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        <span class="w-2 h-2 rounded-full {{ $open ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }}"></span>
                        {{ $open ? 'Відкрито зараз' : 'Закрито' }}
                    </span>
                </div>
                <div class="flex items-center gap-4 mt-1 text-sm text-gray-500">
                    <span>Топік: <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ $store->topic->slug }}</span></span>
                    <span>MQTT ID: <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ $store->mqtt_device_id }}</span></span>
                    @if($store->employee_name)<span>👤 {{ $store->employee_name }}</span>@endif
                </div>
            </div>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('stores.edit', $store) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition">Редагувати</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- KPI --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Сьогодні</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ $statsToday['work_human'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Цей місяць</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ $statsMonth['work_human'] }}</p>
                </div>
                @if($store->topic->has_generator)
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                    <p class="text-xs text-yellow-700">Під генератором (міс.)</p>
                    <p class="text-xl font-bold text-yellow-800 mt-1">{{ $statsMonth['generator_human'] }}</p>
                </div>
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
                    <p class="text-xs text-orange-700">Пального (міс.)</p>
                    <p class="text-xl font-bold text-orange-800 mt-1">{{ $statsMonth['fuel'] }} л</p>
                </div>
                @endif
            </div>

            {{-- Активна сесія --}}
            @if($open && $store->activeSession())
            @php $active = $store->activeSession(); @endphp
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-4">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                <div>
                    <p class="font-medium text-green-800">Магазин відкрито</p>
                    <p class="text-sm text-green-600">з {{ $active->opened_at->format('H:i') }} ({{ $active->opened_at->diffForHumans() }})</p>
                </div>
            </div>
            @endif

            {{-- Пристрої --}}
            @if($store->devices->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-900">Пристрої</h3></div>
                <div class="divide-y divide-gray-50">
                    @foreach($store->devices as $device)
                    <div class="px-5 py-3 flex items-center justify-between text-sm">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full {{ $device->is_active ? 'bg-green-400' : 'bg-gray-300' }}"></span>
                            <span class="font-medium">{{ $device->name ?? $device->mqtt_device_id }}</span>
                            <span class="font-mono text-xs text-gray-400">{{ $device->mqtt_device_id }}</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded">{{ $device->getTypeLabel() }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Сесії --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Сесії роботи</h3>
                    <a href="{{ route('sessions.index') }}?store_id={{ $store->id }}" class="text-sm text-blue-600 hover:underline">Всі сесії →</a>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">Відкрито</th>
                            <th class="px-5 py-3 text-left font-medium">Закрито</th>
                            <th class="px-5 py-3 text-center font-medium">Тривалість</th>
                            <th class="px-5 py-3 text-center font-medium">Статус</th>
                            @if(auth()->user()->isAdmin())<th class="px-5 py-3 text-right font-medium">Дії</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($sessions as $session)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">{{ $session->opened_at->format('d.m H:i') }}</td>
                            <td class="px-5 py-3">{{ $session->closed_at?->format('d.m H:i') ?? '—' }}</td>
                            <td class="px-5 py-3 text-center font-medium">{{ $session->duration_human }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-xs px-2 py-0.5 rounded {{ $session->isOpen() ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $session->isOpen() ? 'відкрито' : 'завершено' }}
                                </span>
                            </td>
                            @if(auth()->user()->isAdmin())
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('sessions.edit', $session) }}" class="text-xs text-blue-600 hover:underline">Ред.</a>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">Сесій ще немає</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-3 border-t border-gray-100">{{ $sessions->links() }}</div>
            </div>

        </div>
    </div>
</x-app-layout>
