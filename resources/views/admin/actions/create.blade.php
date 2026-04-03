<x-layout title="Створити дію">
    <div class="container" style="max-width:600px">
        <h1>Створити дію</h1>
        <p class="text-muted">
            Дія відповідає числовому значенню поля <code>act</code> у MQTT-повідомленні.
            Ідентифікатор — це саме те число, яке передає пристрій (наприклад <code>1</code>, <code>5</code>).
        </p>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.actions.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Числовий ідентифікатор <code>act</code></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                       placeholder="1" required>
                <div class="form-text">Число, яке приходить у полі <code>act</code> від пристрою.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Заголовок</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                       placeholder="Відкриття дверей" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Опис</label>
                <textarea name="description" class="form-control" rows="2"
                          placeholder="Додатковий опис (необов'язково)">{{ old('description') }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Створити</button>
                <a href="{{ route('admin.actions') }}" class="btn btn-outline-secondary">Скасувати</a>
            </div>
        </form>
    </div>
</x-layout>
