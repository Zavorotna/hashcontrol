<x-layout title="Зареєстровані об'єкти">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Зареєстровані об'єкти</h2>
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('user.tracked-objects.create') }}" class="btn btn-primary">+ Додати об'єкт</a>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @forelse($objects->groupBy('type') as $type => $group)
            <h5 class="mt-4">
                @switch($type)
                    @case('shop')        🏪 Магазини / приміщення @break
                    @case('worker')      👤 Працівники @break
                    @case('generator')   ⚡ Генератори @break
                    @case('thermometer') 🌡️ Термометри @break
                    @case('fridge')      🧊 Холодильники @break
                    @case('counter')     📊 Лічильники @break
                    @default             📦 Інше @break
                @endswitch
            </h5>

            <table class="table table-bordered table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Назва</th>
                        <th>Компанія</th>
                        @if($type === 'shop') <th>Орендар</th> @endif
                        @if($type === 'worker') <th>Телефон</th> @endif
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group as $obj)
                    <tr>
                        <td>
                            <a href="{{ route('user.tracked-objects.show', $obj) }}" class="text-decoration-none fw-semibold">
                                {{ $obj->name }}
                            </a>
                        </td>
                        <td>{{ $obj->company->name }}</td>
                        @if($type === 'shop') <td>{{ $obj->tenant_name ?? '—' }}</td> @endif
                        @if($type === 'worker') <td>{{ $obj->phone ?? '—' }}</td> @endif
                        <td class="text-end">
                            <a href="{{ route('user.tracked-objects.show', $obj) }}" class="btn btn-sm btn-outline-primary">Перегляд</a>
                            <a href="{{ route('user.tracked-objects.edit', $obj) }}" class="btn btn-sm btn-outline-secondary">Редагувати</a>
                            <form action="{{ route('user.tracked-objects.destroy', $obj) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Видалити «{{ $obj->name }}»?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Видалити</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p class="text-muted">Об'єктів ще немає.</p>
        @endforelse
    </div>
</x-layout>
