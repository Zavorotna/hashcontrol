<x-layout title="Створити пристрій">
    <div class="container" style="max-width:600px">
        <h1>Створити пристрій</h1>
        <form method="POST" action="{{ route('admin.devices.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Device ID</label>
                <input type="text" name="device_id" class="form-control"
                       value="{{ old('device_id') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Назва</label>
                <input type="text" name="name" class="form-control"
                       list="device-names-list" autocomplete="off"
                       value="{{ old('name') }}"
                       placeholder="Введіть або виберіть із існуючих..." required>
                <datalist id="device-names-list">
                    @foreach($deviceNames as $dname)
                        <option value="{{ $dname }}">
                    @endforeach
                </datalist>
                <div class="form-text">Якщо два пристрої утворюють пару — дайте їм однакову назву і вкажіть роль.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Роль у діапазоні</label>
                @php $rangeVal = old('is_range_start', ''); @endphp
                <div class="d-flex gap-3 mt-1">
                    <div class="form-check">
                        <input class="form-check-input range-cb" type="checkbox"
                               id="cb_start" name="is_range_start" value="1"
                               {{ $rangeVal === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="cb_start">▶ Початок діапазону</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input range-cb" type="checkbox"
                               id="cb_end" name="is_range_start" value="0"
                               {{ $rangeVal === '0' ? 'checked' : '' }}>
                        <label class="form-check-label" for="cb_end">■ Кінець діапазону</label>
                    </div>
                </div>
                <div class="form-text">Обидва незначені = одиничний запис (температура, лічильник тощо).</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Компанія</label>
                <select name="company_id" class="form-select">
                    <option value="">Не призначено</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Користувач</label>
                <select name="user_id" class="form-select">
                    <option value="">Не призначено</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Створити</button>
                <a href="{{ route('admin.devices') }}" class="btn btn-outline-secondary">Скасувати</a>
            </div>
        </form>
    </div>
<script>
    document.querySelectorAll('.range-cb').forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked) {
                document.querySelectorAll('.range-cb').forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });
</script>
</x-layout>
