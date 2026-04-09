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

        {{-- ── Device name (datalist) ────────────────────────────────────── --}}
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

        {{-- ── ON/OFF device ───────────────────────────────────────────────── --}}
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="isOnOff" name="is_on_off" value="1"
                   {{ old('is_on_off') ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="isOnOff">
                Пристрій ON/OFF (генератор, витяжка, реле тощо)
            </label>
            <div class="form-text">Поле <code>data</code> містить значення <code>on</code> або <code>off</code>. Увімкніть для розрахунку часу роботи та суміжної статистики.</div>
        </div>

        {{-- ── Range role (checkboxes) ────────────────────────────────────── --}}
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

        {{-- ── Owner (datalist by email) ──────────────────────────────────── --}}
        <h5 class="mb-3">Власник</h5>
        <div class="mb-3">
            <label class="form-label">Email власника</label>
            <input type="email" name="owner_email" id="ownerEmail" class="form-control"
                list="users-list" autocomplete="off"
                value="{{ old('owner_email') }}"
                placeholder="email@example.com">
            <datalist id="users-list">
                @foreach($users as $user)
                    <option value="{{ $user->email }}"
                            data-name="{{ $user->name }}"
                            data-companies="{{ $user->companies->pluck('name')->join('|||') }}"
                            data-company-ids="{{ $user->companies->pluck('id')->join(',') }}">
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
                <button type="button" class="btn btn-outline-secondary" id="copyPasswordBtn" title="Copy">
                    Copy
                </button>
            </div>
            <div class="form-text">Використовується лише якщо користувача з таким email ще не існує.</div>
        </div>

        <hr>

        {{-- ── Company ─────────────────────────────────────────────────────── --}}
        <h5 class="mb-3">Компанія</h5>

        {{-- Shown when existing user has companies → select from list --}}
        <div id="companySelectGroup" style="display:none" class="mb-3">
            <label class="form-label">Компанія власника</label>
            <select name="company_name_select" id="companySelect" class="form-select">
                <option value="">— виберіть компанію —</option>
            </select>
            <div class="form-text">Власник вже має зареєстровані компанії. Виберіть або введіть нову нижче.</div>
        </div>

        <div class="mb-4" id="companyNameGroup">
            <label class="form-label" id="companyNameLabel">Назва нової компанії</label>
            <input type="text" name="company_name" id="companyName" class="form-control"
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

        {{-- ── Device actions ─────────────────────────────────────────────── --}}
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


        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">Зареєструвати пристрій</button>
            <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary">Скасувати</a>
        </div>
    </form>
</div>

<script>
    // Build users map: email → { name, companies: [{name, id}] }
    const usersMap = {};
    document.querySelectorAll('#users-list option').forEach(opt => {
        const companyNames = opt.dataset.companies ? opt.dataset.companies.split('|||') : [];
        const companyIds   = opt.dataset.companyIds ? opt.dataset.companyIds.split(',') : [];
        const companies = companyNames.map((n, i) => ({ name: n, id: companyIds[i] })).filter(c => c.name);
        usersMap[opt.value] = { name: opt.dataset.name, companies };
    });

    const ownerEmailInput    = document.getElementById('ownerEmail');
    const ownerNameInput     = document.getElementById('ownerName');
    const passwordGroup      = document.getElementById('newUserPasswordGroup');
    const companySelectGroup = document.getElementById('companySelectGroup');
    const companySelect      = document.getElementById('companySelect');
    const companyNameGroup   = document.getElementById('companyNameGroup');
    const companyNameInput   = document.getElementById('companyName');
    const companyNameLabel   = document.getElementById('companyNameLabel');

    function updateCompanyUI(userData) {
        // Clear select options
        companySelect.innerHTML = '<option value="">— виберіть компанію —</option>';

        if (userData && userData.companies.length > 0) {
            // Existing user with companies
            userData.companies.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.name;
                opt.textContent = c.name;
                companySelect.appendChild(opt);
            });
            companySelectGroup.style.display = '';
            companyNameLabel.textContent = 'Або введіть нову компанію';
            companyNameInput.placeholder  = 'Нова компанія (залиште порожнім якщо вибрали вище)';

            // Pre-select first if only one
            if (userData.companies.length === 1) {
                companySelect.value = userData.companies[0].name;
                companyNameInput.value = '';
            }
        } else {
            companySelectGroup.style.display = 'none';
            companyNameLabel.textContent = 'Назва компанії';
            companyNameInput.placeholder  = 'Введіть або виберіть...';
        }
    }

    ownerEmailInput.addEventListener('input', function () {
        const userData = usersMap[this.value];
        if (userData) {
            ownerNameInput.value = userData.name;
            passwordGroup.style.display = 'none';
            updateCompanyUI(userData);
        } else {
            passwordGroup.style.display = '';
            updateCompanyUI(null);
        }
    });

    // When user picks from company select — clear the text input
    companySelect.addEventListener('change', function () {
        if (this.value) {
            companyNameInput.value = '';
        }
    });

    // Copy password button
    document.getElementById('copyPasswordBtn').addEventListener('click', function () {
        const val = document.getElementById('ownerNewPassword').value;
        navigator.clipboard.writeText(val).then(() => {
            this.textContent = 'Copied';
            setTimeout(() => this.textContent = 'Copy', 1500);
        });
    });

    // Blacklist
    document.getElementById('blacklistDevice').addEventListener('change', function () {
        document.getElementById('blacklistReasonGroup').style.display = this.checked ? 'block' : 'none';
    });

    // Range checkboxes — mutually exclusive
    document.querySelectorAll('.range-cb').forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked) {
                document.querySelectorAll('.range-cb').forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
        });
    });

    // Dynamic action rows
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
