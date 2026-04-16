<x-layout title="Редагувати компанію">
    <div class="container py-4" style="max-width:480px">
        <h1 class="mb-4">Редагувати компанію</h1>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.companies.update', $company) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Назва <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="{{ old('name', $company->name) }}" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Власник</label>
                <select name="user_id" class="form-select">
                    <option value="">— не призначено —</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ $company->user_id == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Зберегти</button>
                <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">Скасувати</a>
            </div>
        </form>
    </div>
</x-layout>
