<x-layout title="Редагувати дію">
    <div class="container" style="max-width:600px">
        <h1>Редагувати дію</h1>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.actions.update', $action) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Числовий ідентифікатор <code>act</code></label>
                <input type="text" name="name" class="form-control"
                       value="{{ old('name', $action->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Заголовок</label>
                <input type="text" name="title" class="form-control"
                       value="{{ old('title', $action->title) }}" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Опис</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $action->description) }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Оновити</button>
                <a href="{{ route('admin.actions') }}" class="btn btn-outline-secondary">Скасувати</a>
            </div>
        </form>
    </div>
</x-layout>
