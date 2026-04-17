<x-layout title="Налаштування">
<div class="container py-4" style="max-width: 480px">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ auth()->user()->name }}</h2>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">Вийти</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header fw-semibold">Зміна пароля</div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('user.settings.password') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Поточний пароль</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Новий пароль</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="mb-4">
                    <label class="form-label">Повторіть новий пароль</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Змінити пароль</button>
            </form>
        </div>
    </div>

</div>
</x-layout>
