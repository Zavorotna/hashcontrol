<x-layout title="Редагувати пристрій">
    <div class="container" style="max-width:680px">
        <h1>Редагувати пристрій</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form method="POST" action="{{ route('admin.devices.update', $device) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Device ID</label>
                <input type="text" name="device_id" class="form-control"
                       value="{{ old('device_id', $device->device_id) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Назва</label>
                <input type="text" name="name" class="form-control"
                       list="device-names-list" autocomplete="off"
                       value="{{ old('name', $device->name) }}"
                       placeholder="Введіть або виберіть із існуючих..." required>
                <datalist id="device-names-list">
                    @foreach($deviceNames as $dname)
                        <option value="{{ $dname }}">
                    @endforeach
                </datalist>
                <div class="form-text">Якщо два пристрої утворюють пару — дайте їм однакову назву і вкажіть роль.</div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="isOnOff" name="is_on_off" value="1"
                       {{ old('is_on_off', $device->is_on_off) ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="isOnOff">
                    Пристрій ON/OFF (генератор, витяжка, реле тощо)
                </label>
                <div class="form-text">Позначте, якщо пристрій надсилає <code>on</code>/<code>off</code> у полі data.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Роль у діапазоні</label>
                @php
                    $saved = old('is_range_start', $device->getRawOriginal('is_range_start'));
                    if (is_null($saved))   $rangeVal = '';
                    elseif ($saved)        $rangeVal = '1';
                    else                   $rangeVal = '0';
                @endphp
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
                <div class="form-text">Обидва незначені = одиничний запис (без пари).</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Компанія</label>
                <select name="company_id" class="form-select">
                    <option value="">Не призначено</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ $device->company_id == $company->id ? 'selected' : '' }}>
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
                    <option value="{{ $user->id }}" {{ $device->user_id == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Оновити</button>
                <a href="{{ route('admin.devices') }}" class="btn btn-outline-secondary">Скасувати</a>
            </div>
        </form>

        {{-- ── Register objects from device data values ─────────────────────── --}}
        @if($allDataValues->isNotEmpty())
        <hr class="my-4">
        <h5 class="mb-2">Зареєструвати об'єкти</h5>
        <p class="text-muted small mb-3">
            Унікальні значення <code>data</code>, що надходили з цього пристрою.
            Відмітьте ті, що є ідентифікаторами об'єктів — їх буде зареєстровано для компанії пристрою.
        </p>

        <form method="POST" action="{{ route('admin.devices.register-objects', $device) }}">
            @csrf
            <div class="card mb-3">
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">
                                    <input type="checkbox" id="selectAllObjects" class="form-check-input" title="Вибрати всі">
                                </th>
                                <th>ID (data)</th>
                                <th>Статус</th>
                                <th>Назва <span class="text-danger">*</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allDataValues as $dataVal)
                            @php $alreadyRegistered = $registeredExternalIds->contains($dataVal); @endphp
                            <tr class="{{ $alreadyRegistered ? 'table-success' : '' }}">
                                <td>
                                    @if(!$alreadyRegistered)
                                    <input type="checkbox" class="form-check-input object-cb"
                                           name="objects[{{ $dataVal }}][register]" value="1"
                                           id="obj_{{ $loop->index }}">
                                    @endif
                                </td>
                                <td>
                                    <label for="obj_{{ $loop->index }}" class="mb-0">
                                        <code>{{ $dataVal }}</code>
                                    </label>
                                </td>
                                <td class="small">
                                    @if($alreadyRegistered)
                                        <span class="badge bg-success">зареєстровано</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$alreadyRegistered)
                                    <input type="text"
                                           name="objects[{{ $dataVal }}][name]"
                                           class="form-control form-control-sm obj-name-input"
                                           placeholder="Введіть назву"
                                           disabled
                                           required>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if(!$device->company_id)
                <p class="text-warning small">Спочатку призначте пристрою компанію — об'єкти реєструються в межах компанії.</p>
            @else
                <button type="submit" class="btn btn-outline-primary btn-sm">Зареєструвати вибрані</button>
            @endif
        </form>
        @endif

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

    // Objects table
    document.querySelectorAll('.object-cb').forEach(cb => {
        const nameInput = cb.closest('tr').querySelector('.obj-name-input');
        cb.addEventListener('change', function () {
            nameInput.disabled = !this.checked;
            if (!this.checked) nameInput.value = '';
        });
    });
    const selectAll = document.getElementById('selectAllObjects');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.object-cb').forEach(cb => {
                cb.checked = this.checked;
                cb.dispatchEvent(new Event('change'));
            });
        });
    }
</script>
</x-layout>
