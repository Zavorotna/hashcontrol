<x-layout title="Користувачі">
<div class="container py-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Користувачі</h1>
        <span class="text-muted small">{{ $users->count() }} зареєстровано</span>
    </div>

    <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>Ім'я</th>
                <th>Email</th>
                <th>Компанії</th>
                <th class="text-center" style="width:80px">Пристрої</th>
                <th style="width:120px"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td class="small text-muted">
                    {{ $user->companies->pluck('name')->join(', ') ?: '—' }}
                </td>
                <td class="text-center">{{ $user->devices_count }}</td>
                <td class="d-flex gap-1">
                    <a href="{{ route('admin.users.dashboard', $user) }}" class="btn btn-sm btn-outline-primary">Дашборд</a>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                          onsubmit="return confirm('Видалити {{ addslashes($user->name) }}?\nПристрої та компанії залишаться, але будуть відв\'язані від цього користувача.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Видалити</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted">Користувачів немає.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
</x-layout>
