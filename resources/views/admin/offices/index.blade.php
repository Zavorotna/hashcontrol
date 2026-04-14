<x-layout title="Об'єкти">
    <div class="container">
        <h1>Об'єкти</h1>
        <a href="{{ route('admin.offices.create') }}" class="btn btn-primary mb-3">Створити об'єкт</a>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Office Number</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($offices as $office)
                <tr>
                    <td>{{ $office->id }}</td>
                    <td>{{ $office->office_number }}</td>
                    <td>{{ $office->name }}</td>
                    <td>{{ $office->company ? $office->company->name : 'Не призначено' }}</td>
                    <td>
                        <a href="{{ route('admin.offices.edit', $office) }}" class="btn btn-sm btn-warning">Редагувати</a>
                        <form method="POST" action="{{ route('admin.offices.destroy', $office) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Ви впевнені?')">Видалити</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>