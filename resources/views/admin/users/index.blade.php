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

        {{-- ── Список користувачів ────────────────────────────────────────────── --}}
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="mb-0">Користувачі</h1>
                <span class="text-muted small">{{ $users->count() }} зареєстровано</span>
            </div>

            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Компанія</th>
                        <th>Ім'я</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th class="text-center" style="width:70px">Пристрої</th>
                        <th style="width:130px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="small text-muted">
                            {{ $user->companies->pluck('name')->join(', ') ?: '—' }}
                        </td>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td class="small">{{ $user->email }}</td>
                        <td class="small">{{ $user->phone ?: '—' }}</td>
                        <td class="text-center">{{ $user->devices_count }}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('admin.users.dashboard', $user) }}"
                               class="btn btn-sm btn-outline-primary">Дашборд</a>
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
        </div>

        {{-- ── Форма створення ────────────────────────────────────────────────── --}}
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
                        <div class="mb-4" id="newCompanyGroup">
                            <label class="form-label">або створити нову компанію</label>
                            <input type="text" name="new_company_name" class="form-control"
                                   value="{{ old('new_company_name') }}"
                                   placeholder="Назва нової компанії">
                            <div class="form-text">Якщо обрано існуючу компанію — це поле ігнорується.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Створити</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
</x-layout>
