<x-layout title="Дії">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">Дії</h1>
            <a href="{{ route('admin.actions.create') }}" class="btn btn-primary">+ Створити дію</a>
        </div>
        <p class="text-muted small">
            Кожна дія відповідає числовому значенню поля <code>act</code> у MQTT-повідомленні.
        </p>
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:70px">act</th>
                    <th>Заголовок</th>
                    <th>Опис</th>
                    <th style="width:140px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($actions as $action)
                <tr>
                    <td><code>{{ $action->name }}</code></td>
                    <td>{{ $action->title }}</td>
                    <td class="text-muted small">{{ $action->description }}</td>
                    <td>
                        <a href="{{ route('admin.actions.edit', $action) }}" class="btn btn-sm btn-warning">Редагувати</a>
                        <form method="POST" action="{{ route('admin.actions.destroy', $action) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Видалити дію?')">Видалити</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
