<x-layout title="Об'єкти">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">Об'єкти</h1>
            <a href="{{ route('admin.offices.create') }}" class="btn btn-primary btn-sm">+ Створити</a>
        </div>
        <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">ID</th>
                    <th style="width:100px">Номер</th>
                    <th>Назва</th>
                    <th>Компанія</th>
                    <th style="width:1px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($offices as $office)
                <tr>
                    <td class="text-muted small">{{ $office->id }}</td>
                    <td class="small">{{ $office->office_number }}</td>
                    <td>{{ $office->name }}</td>
                    <td class="text-muted small">{{ $office->company ? $office->company->name : '—' }}</td>
                    <td class="text-nowrap">
                        <div class="btn-actions">
                            <a href="{{ route('admin.offices.edit', $office) }}"
                               class="btn btn-sm btn-outline-secondary" title="Редагувати">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.offices.destroy', $office) }}"
                                  onsubmit="return confirm('Видалити об\'єкт?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Видалити">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</x-layout>
