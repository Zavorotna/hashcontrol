<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'hashcontrol' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ── Mobile tweaks ── */
        @media (max-width: 767px) {
            .container { padding-left: 12px; padding-right: 12px; }
            main.container { margin-top: 1rem !important; }
            h1 { font-size: 1.4rem; }
            h2 { font-size: 1.2rem; }
            .btn-lg { font-size: 1rem; padding: .5rem 1rem; }

            /* Tables: scroll on small screens */
            .table-responsive-always { overflow-x: auto; -webkit-overflow-scrolling: touch; }

            /* Cards stack better */
            .card { margin-bottom: 1rem; }

            /* Period selector wraps nicely */
            .period-selector { gap: .4rem !important; }
            .period-selector .btn { font-size: .75rem; padding: .25rem .5rem; }

            /* Navbar name truncate */
            .nav-link.text-secondary { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

            /* Forms full-width buttons on mobile */
            .form-actions-mobile .btn { width: 100%; margin-bottom: .5rem; }

            /* Hide less important table columns on mobile */
            .col-hide-mobile { display: none; }

            /* Tighten page padding */
            .py-4 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ auth()->check() ? route('dashboard') : url('/') }}">hashcontrol</a>
            <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Меню">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                @auth
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @if(auth()->user()->role === 'admin')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.devices*') ? 'active' : '' }}"
                               href="{{ route('admin.devices') }}">Пристрої</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
                               href="{{ route('admin.users') }}">Користувачі</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('user.tracked-objects*') ? 'active' : '' }}"
                               href="{{ route('user.tracked-objects.index') }}">Об'єкти</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.actions*') ? 'active' : '' }}"
                               href="{{ route('admin.actions') }}">Дії</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.blacklisted*') ? 'active' : '' }}"
                               href="{{ route('admin.blacklisted_devices') }}">Ігнор</a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('user.index') || request()->routeIs('user.devices*') ? 'active' : '' }}"
                               href="{{ route('user.index') }}">Пристрої</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('user.companies') || request()->routeIs('user.tracked-objects*') ? 'active' : '' }}"
                               href="{{ route('user.companies') }}">Компанії та об'єкти</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('user.events') ? 'active' : '' }}"
                               href="{{ route('user.events') }}">Події</a>
                        </li>
                    @endif
                </ul>

                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-secondary small {{ request()->routeIs('user.settings*') ? 'active' : '' }}"
                           href="{{ route('user.settings') }}">{{ auth()->user()->name }}</a>
                    </li>
                </ul>
                @else
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Увійти</a>
                    </li>
                </ul>
                @endauth
            </div>
        </div>
    </nav>

    <main class="container mt-4 pb-5">
        {{ $slot }}
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
