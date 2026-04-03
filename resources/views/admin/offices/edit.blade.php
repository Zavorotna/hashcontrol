<x-layout title="Редагувати офіс">
    <div class="container">
        <h1>Редагувати офіс</h1>
        <form method="POST" action="{{ route('admin.offices.update', $office) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label>Номер офісу</label>
                <input type="text" name="office_number" class="form-control" value="{{ $office->office_number }}" required>
            </div>
            <div class="mb-3">
                <label>Назва</label>
                <input type="text" name="name" class="form-control" value="{{ $office->name }}">
            </div>
            <div class="mb-3">
                <label>Компанія</label>
                <select name="company_id" class="form-control">
                    <option value="">Не призначено</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ $office->company_id == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Оновити</button>
        </form>
    </div>
</x-layout>