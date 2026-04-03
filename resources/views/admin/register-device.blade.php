<x-layout title="Реєстрація пристрою">
<div class="container py-4" style="max-width: 720px">
    <h2 class="mb-1">Реєстрація пристрою</h2>
    <p class="text-muted mb-4">
        <strong>Device ID:</strong> <code>{{ $message->device_id }}</code> &nbsp;|&nbsp;
        <strong>act:</strong> {{ $message->action ?? '—' }} &nbsp;|&nbsp;
        <strong>data:</strong> <code>{{ $message->data }}</code>
    </p>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.registerDevice', $message->id) }}">
        @csrf

        {{-- ── Назва пристрою (datalist) ──────────────────────────────────── --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">Назва пристрою</label>
            <input type="text" name="device_name" class="form-control"
                   list="device-names-list" autocomplete="off"
                   value="{{ old('device_name', 'Device ' . $message->device_id) }}"
                   placeholder="Введіть або виберіть із існуючих...">
            <datalist id="device-names-list">
                @foreach($deviceNames as $dname)
                    <option value="{{ $dname }}">
                @endforeach
            </datalist>
            <div class="form-text">
                Якщо два пристрої утворюють пару — дайте їм однакову назву і вкажіть роль нижче.
            </div>
        </div>

        {{-- ── Роль у діапазоні (чекбокси) ───────────────────────────────── --}}
        <div class="mb-4">
            <label class="form-label fw-semibold">Роль у діапазоні</label>
            <div class="form-text mb-2">
                Якщо пристрій є частиною пари (відкриття/закриття, запуск/зупинка, прихід/відхід) —
                позначте його роль. Для вимірювань (температура, лічильник) — залиште обидва незначеними.
            </div>
            @php $rangeVal = old('is_range_start', ''); @endphp
            <div class="d-flex gap-3">
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

        <div class="mb-2 form-check">
            <input type="checkbox" name="blacklist_device" class="form-check-input" id="blacklistDevice"
                {{ old('blacklist_device') ? 'checked' : '' }}>
            <label class="form-check-label" for="blacklistDevice">Додати в чорний список замість реєстрації</label>
        </div>
        <div class="mb-4" id="blacklistReasonGroup" style="display: {{ old('blacklist_device') ? 'block' : 'none' }};">
            <label class="form-label">Причина</label>
            <input type="text" name="blacklist_reason" class="form-control"
                value="{{ old('blacklist_reason', 'Невідома причина') }}">
        </div>

        <hr>

        {{-- ── Власник (datalist по email) ─────────────────────────────────── --}}
        <h5 class="mb-3">Власник</h5>
        <div class="mb-3">
            <label class="form-label">Email власника</label>
            <input type="email" name="owner_email" id="ownerEmail" class="form-control"
                list="users-list" autocomplete="off"
                value="{{ old('owner_email') }}"
                placeholder="email@example.com">
            <datalist id="users-list">
                @foreach($users as $user)
                    <option value="{{ $user->email }}" data-name="{{ $user->name }}">
                @endforeach
            </datalist>
            <div class="form-text">Виберіть існуючого або введіть новий email — обліковий запис буде створено.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Ім'я</label>
            <input type="text" name="owner_name" id="ownerName" class="form-control"
                value="{{ old('owner_name') }}"
                placeholder="Ім'я та прізвище">
            <div class="form-text">Підтягується автоматично для існуючих користувачів. Для нового — введіть вручну.</div>
        </div>
        <div class="mb-4" id="newUserPasswordGroup">
            <label class="form-label">Пароль для нового користувача</label>
            <div class="input-group">
                <input type="text" name="owner_new_password" id="ownerNewPassword" class="form-control font-monospace"
                    value="{{ old('owner_new_password', $generatedPassword) }}">
                <button type="button" class="btn btn-outline-secondary" id="copyPasswordBtn" title="Скопіювати">
                    📋
                </button>
            </div>
            <div class="form-text">Використовується лише якщо користувача з таким email ще не існує.</div>
        </div>

        <hr>

        {{-- ── Компанія (datalist) ─────────────────────────────────────────── --}}
        <h5 class="mb-3">Компанія</h5>
        <div class="mb-4">
            <label class="form-label">Назва компанії</label>
            <input type="text" name="company_name" class="form-control"
                list="companies-list" autocomplete="off"
                value="{{ old('company_name') }}"
                placeholder="Введіть або виберіть...">
            <datalist id="companies-list">
                @foreach($companies as $company)
                    <option value="{{ $company->name }}">
                @endforeach
            </datalist>
        </div>

        <hr>

        {{-- ── Дії пристрою ───────────────────────────────────────────────── --}}
        <h5 class="mb-3">Дії пристрою</h5>
        <p class="text-muted small">Додайте одну або більше дій, які цей пристрій може виконувати.</p>

        <div id="actions-container">
            @if(old('actions'))
                @foreach(old('actions') as $i => $oldAction)
                <div class="action-row d-flex align-items-center gap-2 mb-2">
                    <select name="actions[{{ $i }}][action_id]" class="form-select">
                        <option value="">— виберіть дію —</option>
                        @foreach($actions as $action)
                            <option value="{{ $action->id }}"
                                {{ ($oldAction['action_id'] ?? '') == $action->id ? 'selected' : '' }}>
                                {{ $action->title }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-action">✕</button>
                </div>
                @endforeach
            @endif
        </div>

        <button type="button" id="add-action-btn" class="btn btn-outline-primary btn-sm mb-4">
            + Додати дію
        </button>

        <template id="action-row-template">
            <div class="action-row d-flex align-items-center gap-2 mb-2">
                <select name="actions[__INDEX__][action_id]" class="form-select">
                    <option value="">— виберіть дію —</option>
                    @foreach($actions as $action)
                        <option value="{{ $action->id }}">{{ $action->title }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-danger btn-sm remove-action">✕</button>
            </div>
        </template>

        <hr>

        {{-- ── Реєстрація об'єкта за значенням data ───────────────────────── --}}
        <h5 class="mb-2">Зареєструвати об'єкт</h5>
        <p class="text-muted small mb-3">
            Якщо поле <code>data: {{ $message->data }}</code> є ідентифікатором офісу, магазину або іншого об'єкта —
            можна зареєструвати його одразу. Власник зможе пізніше заповнити деталі (назву, орендаря, контакти тощо).
        </p>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="registerObject" name="register_object" value="1"
                   {{ old('register_object') ? 'checked' : '' }}>
            <label class="form-check-label" for="registerObject">
                Зареєструвати <code>{{ $message->data }}</code> як об'єкт
            </label>
        </div>

        <div id="objectFields" style="display: {{ old('register_object') ? 'block' : 'none' }};">
            <div class="mb-3">
                <label class="form-label">Назва <span class="text-muted small">(необов'язково)</span></label>
                <input type="text" name="object_name" class="form-control"
                       value="{{ old('object_name') }}"
                       placeholder="Залиште порожнім — власник заповнить">
                <div class="form-text">Тип, контакти та інші деталі власник вкаже самостійно.</div>
            </div>
        </div>

        <hr>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">Зареєструвати пристрій</button>
            <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary">Скасувати</a>
        </div>
    </form>
</div>

<script>
    // Автопідстановка імені + приховування пароля при виборі існуючого email
    const ownerEmailInput    = document.getElementById('ownerEmail');
    const ownerNameInput     = document.getElementById('ownerName');
    const passwordGroup      = document.getElementById('newUserPasswordGroup');
    const usersMap = {};
    document.querySelectorAll('#users-list option').forEach(opt => {
        usersMap[opt.value] = opt.dataset.name;
    });
    ownerEmailInput.addEventListener('input', function () {
        const name = usersMap[this.value];
        if (name) {
            ownerNameInput.value = name;
            passwordGroup.style.display = 'none';
        } else {
            passwordGroup.style.display = '';
        }
    });

    // Показ/приховування полів об'єкта
    document.getElementById('registerObject').addEventListener('change', function () {
        document.getElementById('objectFields').style.display = this.checked ? 'block' : 'none';
    });

    // Кнопка копіювання пароля
    document.getElementById('copyPasswordBtn').addEventListener('click', function () {
        const val = document.getElementById('ownerNewPassword').value;
        navigator.clipboard.writeText(val).then(() => {
            this.textContent = '✓';
            setTimeout(() => this.textContent = '📋', 1500);
        });
    });

    // Чорний список
    document.getElementById('blacklistDevice').addEventListener('change', function () {
        document.getElementById('blacklistReasonGroup').style.display = this.checked ? 'block' : 'none';
    });

    // Чекбокси діапазону — взаємовиключні
    document.querySelectorAll('.range-cb').forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked) {
                document.querySelectorAll('.range-cb').forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });

    // Динамічні рядки дій
    let actionIndex = {{ old('actions') ? count(old('actions')) : 0 }};
    const container = document.getElementById('actions-container');
    const template  = document.getElementById('action-row-template');

    document.getElementById('add-action-btn').addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', actionIndex++);
        const div  = document.createElement('div');
        div.innerHTML = html;
        const row = div.firstElementChild;
        container.appendChild(row);
        row.querySelector('.remove-action').addEventListener('click', () => row.remove());
    });

    document.querySelectorAll('.remove-action').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.action-row').remove());
    });
</script>
</x-layout>
