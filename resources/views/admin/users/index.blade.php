<x-layout title="Користувачі">
<div class="container py-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <div class="row g-4">

        {{-- ── Ліва колонка: користувачі + компанії ──────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Користувачі --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="mb-0">Користувачі</h1>
                <span class="text-muted small">{{ $users->count() }} зареєстровано</span>
            </div>

            <table class="table table-bordered table-sm align-middle mb-5">
                <thead class="table-light">
                    <tr>
                        <th>Компанія</th>
                        <th>Ім'я</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th class="text-center" style="width:70px">Пристрої</th>
                        <th style="width:150px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="small text-muted">
                            @foreach($user->companies as $c)
                                {{ $c->name }}
                                @if($c->pivot->position)
                                    <span class="badge bg-light text-dark border">{{ $c->pivot->position }}</span>
                                @endif
                                @if(!$loop->last), @endif
                            @endforeach
                            @if($user->companies->isEmpty())—@endif
                        </td>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td class="small">{{ $user->email }}</td>
                        <td class="small">{{ $user->phone ?: '—' }}</td>
                        <td class="text-center">{{ $user->devices_count }}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('admin.users.dashboard', $user) }}"
                               class="btn btn-sm btn-outline-primary">Дашборд</a>
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="btn btn-sm btn-outline-secondary">Ред.</a>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirm('Видалити {{ addslashes($user->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">✕</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted">Користувачів немає.</td></tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Компанії --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0 h4">Компанії</h2>
                <span class="text-muted small">{{ $companies->count() }}</span>
            </div>

            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Назва</th>
                        <th>Власник</th>
                        <th class="text-center" style="width:80px">Пристрої</th>
                        <th style="width:120px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                    <tr>
                        <td class="fw-semibold">{{ $company->name }}</td>
                        <td class="small text-muted">{{ $company->user?->name ?? '—' }}</td>
                        <td class="text-center">{{ $company->devices_count }}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('admin.companies.edit', $company) }}"
                               class="btn btn-sm btn-outline-secondary">Ред.</a>
                            <form method="POST" action="{{ route('admin.companies.destroy', $company) }}"
                                  onsubmit="return confirm('Видалити компанію «{{ addslashes($company->name) }}» та всі її дані?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">✕</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-muted">Компаній немає.</td></tr>
                    @endforelse
                </tbody>
            </table>

        </div>

        {{-- ── Права колонка: форма створення користувача ─────────────────────── --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header fw-semibold">Новий користувач</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Ім'я <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" class="form-control"
                                   value="{{ old('phone') }}" placeholder="+380 XX XXX XX XX">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль <span class="text-danger">*</span></label>
                            <input type="text" name="password" class="form-control font-monospace"
                                   value="{{ old('password', \Illuminate\Support\Str::random(10)) }}" required minlength="6">
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label">Компанія (існуюча)</label>
                            <select name="company_id" class="form-select" id="companySelect">
                                <option value="">— не обирати —</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3" id="newCompanyGroup">
                            <label class="form-label">або нова компанія</label>
                            <input type="text" name="new_company_name" class="form-control"
                                   value="{{ old('new_company_name') }}"
                                   placeholder="Назва нової компанії">
                            <div class="form-text">Якщо обрано існуючу — це поле ігнорується.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Посада</label>
                            <input type="text" name="position" class="form-control"
                                   list="position-list" autocomplete="off"
                                   value="{{ old('position', 'owner') }}"
                                   placeholder="owner, guard, admin...">
                            <datalist id="position-list">
                                <option value="owner">
                                <option value="admin">
                                <option value="guard">
                                <option value="cashier">
                                <option value="manager">
                                <option value="operator">
                            </datalist>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Створити</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
</x-layout>
