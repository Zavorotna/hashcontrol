<x-layout title="Редагувати компанію">
    <div class="container">
        <h1>Редагувати компанію</h1>
        <form method="POST" action="{{ route('admin.companies.update', $company) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label>Назва</label>
                <input type="text" name="name" class="form-control" value="{{ $company->name }}" required>
            </div>
            <div class="mb-3">
                <label>Користувач</label>
                <select name="user_id" class="form-control" required>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $company->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Оновити</button>
        </form>
    </div>
</x-layout>