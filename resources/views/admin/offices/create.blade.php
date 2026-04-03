<x-layout title="Створити офіс">
    <div class="container">
        <h1>Створити офіс</h1>
        <form method="POST" action="{{ route('admin.offices.store') }}">
            @csrf
            <div class="mb-3">
                <label>Номер офісу</label>
                <input type="text" name="office_number" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Назва</label>
                <input type="text" name="name" class="form-control">
            </div>
            <div class="mb-3">
                <label>Компанія</label>
                <select name="company_id" class="form-control">
                    <option value="">Unassigned</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Створити</button>
        </form>
    </div>
</x-layout>