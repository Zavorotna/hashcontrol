<x-layout title="{{ isset($trackedObject) ? 'Редагувати об\'єкт' : 'Додати об\'єкт' }}">
<div class="container py-4" style="max-width: 640px">
    <h2>{{ isset($trackedObject) ? 'Редагувати об\'єкт' : 'Додати об\'єкт' }}</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <form
        action="{{ isset($trackedObject) ? route('user.tracked-objects.update', $trackedObject) : route('user.tracked-objects.store') }}"
        method="POST">
        @csrf
        @if(isset($trackedObject)) @method('PUT') @endif

        {{-- MQTT ID --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">
                ID об'єкта <small class="text-muted">(поле <code>data</code> з MQTT)</small>
            </label>
            @if(isset($trackedObject))
                <input type="text" class="form-control bg-light" readonly
                    value="{{ $trackedObject->external_id }}">
                <input type="hidden" name="external_id" value="{{ $trackedObject->external_id }}">
                <div class="form-text">ID не можна змінювати після реєстрації об'єкта.</div>
            @else
                <input type="text" name="external_id" class="form-control" required
                    placeholder="напр. 203, W001, GEN_MAIN"
                    value="{{ old('external_id', request('external_id', '')) }}">
                <div class="form-text">Знайдіть значення в колонці «ID (MQTT)» таблиці подій.</div>
            @endif
        </div>

        {{-- Тип --}}
        @php
        $defaultTypes = ['shop', 'worker', 'generator', 'thermometer', 'fridge', 'counter', 'other'];
        $typeOptions  = collect($defaultTypes)
            ->merge($existingTypes ?? [])
            ->unique()->sort()->values();
        $currentType  = old('type', $trackedObject->type ?? 'shop');
        @endphp
        <div class="mb-3">
            <label class="form-label fw-semibold">Тип</label>
            <input type="text" name="type" id="typeSelect" class="form-control"
                   list="type-list" autocomplete="off" required
                   value="{{ $currentType }}"
                   placeholder="shop, worker, generator...">
            <datalist id="type-list">
                @foreach($typeOptions as $t)
                    <option value="{{ $t }}">
                @endforeach
            </datalist>
            <div class="form-text">Виберіть зі списку або введіть власний тип.</div>
        </div>

        {{-- Назва --}}
        <div class="mb-3">
            <label class="form-label fw-semibold" id="nameLabel">Назва</label>
            <input type="text" name="name" class="form-control" required
                placeholder="напр. Магазин Puma / Іваненко Олег"
                value="{{ old('name', $trackedObject->name ?? '') }}">
        </div>

        {{-- Компанія --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">Компанія</label>
            <select name="company_id" class="form-select">
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                        {{ old('company_id', $trackedObject->company_id ?? '') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Орендар (тільки для shop) --}}
        <div class="mb-3 type-field shop-field">
            <label class="form-label fw-semibold">Орендар / назва ФОП</label>
            <input type="text" name="tenant_name" class="form-control"
                placeholder="напр. ТОВ Пума Україна"
                value="{{ old('tenant_name', $trackedObject->tenant_name ?? '') }}">
        </div>

        <hr>
        <h6 class="text-muted mb-3">Контактні дані</h6>

        {{-- Email --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control"
                placeholder="contact@example.com"
                value="{{ old('email', $trackedObject->email ?? '') }}">
        </div>

        {{-- Телефон --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">Телефон</label>
            <input type="text" name="phone" class="form-control"
                placeholder="+380 XX XXX XX XX"
                value="{{ old('phone', $trackedObject->phone ?? '') }}">
        </div>

        {{-- Адреса --}}
        <div class="mb-3 type-field hide-for-worker">
            <label class="form-label fw-semibold">Адреса</label>
            <input type="text" name="address" class="form-control"
                placeholder="вул. Хрещатик 1, оф. 3"
                value="{{ old('address', $trackedObject->address ?? '') }}">
        </div>

        {{-- Примітки --}}
        <div class="mb-4">
            <label class="form-label fw-semibold">Примітки</label>
            <textarea name="notes" class="form-control" rows="3"
                placeholder="Довільний текст...">{{ old('notes', $trackedObject->notes ?? '') }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Зберегти</button>
            @if(isset($trackedObject))
                <a href="{{ route('user.tracked-objects.show', $trackedObject) }}" class="btn btn-outline-secondary">Скасувати</a>
            @else
                <a href="{{ route('user.tracked-objects.index') }}" class="btn btn-outline-secondary">Скасувати</a>
            @endif
        </div>
    </form>
</div>

<script>
    const typeInput  = document.getElementById('typeSelect');
    const nameLabel  = document.getElementById('nameLabel');
    const shopFields = document.querySelectorAll('.shop-field');
    const workerHide = document.querySelectorAll('.hide-for-worker');

    function applyType() {
        const t = typeInput.value.trim().toLowerCase();
        nameLabel.textContent = t === 'worker' ? 'ПІБ працівника' : 'Назва';
        shopFields.forEach(el => { el.style.display = t === 'shop' ? '' : 'none'; });
        workerHide.forEach(el => { el.style.display = t === 'worker' ? 'none' : ''; });
    }

    typeInput.addEventListener('input', applyType);
    typeInput.addEventListener('change', applyType);
    applyType();
</script>
</x-layout>
