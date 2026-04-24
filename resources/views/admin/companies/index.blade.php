<x-layout title="Компанії">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">Компанії</h1>
            <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">+ Створити</a>
        </div>
        <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">ID</th>
                    <th>Назва</th>
                    <th>Власник</th>
                    <th style="width:1px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($companies as $company)
                <tr>
                    <td class="text-muted small">{{ $company->id }}</td>
                    <td>{{ $company->name }}</td>
                    <td class="text-muted small">{{ $company->user?->name ?? '—' }}</td>
                    <td class="text-nowrap">
                        <div class="btn-actions">
                            <a href="{{ route('admin.companies.edit', $company) }}"
                               class="btn btn-sm btn-outline-secondary" title="Редагувати">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.companies.destroy', $company) }}"
                                  onsubmit="return confirm('Видалити компанію «{{ addslashes($company->name) }}»?')">
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
