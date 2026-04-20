<x-layout title="Дії">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">Дії</h1>
            <a href="{{ route('admin.actions.create') }}" class="btn btn-primary">+ Створити дію</a>
        </div>
        <p class="text-muted small">
            Кожна дія відповідає числовому значенню поля <code>act</code> у MQTT-повідомленні.
        </p>
        <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:70px">act</th>
                    <th>Заголовок</th>
                    <th class="col-hide-mobile">Опис</th>
                    <th style="width:1px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($actions as $action)
                <tr>
                    <td><code>{{ $action->name }}</code></td>
                    <td>{{ $action->title }}</td>
                    <td class="text-muted small col-hide-mobile">{{ $action->description }}</td>
                    <td class="text-nowrap">
                        <div class="btn-actions">
                            <a href="{{ route('admin.actions.edit', $action) }}"
                               class="btn btn-sm btn-outline-secondary" title="Редагувати">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.actions.destroy', $action) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Видалити дію?')" title="Видалити">
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
