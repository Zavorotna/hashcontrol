<x-layout title="Створити компанію">
    <div class="container">
        <h1>Створити компанію</h1>
        <form method="POST" action="{{ route('admin.companies.store') }}">
            @csrf
            <div class="mb-3">
                <label>Назва</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Користувач</label>
                <select name="user_id" class="form-control" required>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Створити</button>
        </form>
    </div>
</x-layout>