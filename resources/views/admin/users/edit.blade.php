<x-layout title="Редагувати користувача">
<div class="container py-4" style="max-width:540px">

    <h1 class="mb-4">Редагувати користувача</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Ім'я <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $user->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control"
                   value="{{ old('email', $user->email) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Телефон</label>
            <input type="text" name="phone" class="form-control"
                   value="{{ old('phone', $user->phone) }}" placeholder="+380 XX XXX XX XX">
        </div>

        <div class="mb-3">
            <label class="form-label">Новий пароль <span class="text-muted small">(залишити порожнім — не змінювати)</span></label>
            <input type="text" name="password" class="form-control font-monospace"
                   value="" autocomplete="new-password" minlength="6">
        </div>

        <hr>

        <div class="mb-3">
            <label class="form-label">Додати до компанії</label>
            <select name="company_id" class="form-select">
                <option value="">— не змінювати —</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                        {{ $user->companies->contains($company->id) ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label">Посада</label>
            <input type="text" name="position" class="form-control"
                   list="position-list" autocomplete="off"
                   value="{{ old('position', $user->companies->first()?->pivot?->position ?? 'owner') }}"
                   placeholder="owner, guard, admin...">
            <datalist id="position-list">
                <option value="owner">
                <option value="admin">
                <option value="guard">
                <option value="cashier">
                <option value="manager">
                <option value="operator">
            </datalist>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Зберегти</button>
            <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">Скасувати</a>
        </div>
    </form>
</div>
</x-layout>
