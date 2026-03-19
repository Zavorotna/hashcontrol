<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-bold text-blue-600 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                    </svg>
                    hash-controll
                </a>
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">Дашборд</a>
                    <a href="{{ route('statistics') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('statistics') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">Статистика</a>
                    <a href="{{ route('stores.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('stores.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">Магазини</a>
                    <a href="{{ route('sessions.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('sessions.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">Сесії</a>
                    @if(auth()->user()->isAdmin())
                    <div class="relative" x-data="{ adminOpen: false }">
                        <button @click="adminOpen = !adminOpen" class="px-3 py-2 rounded-md text-sm font-medium flex items-center gap-1 transition {{ request()->routeIs('topics.*','devices.*','users.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            Адмін <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="adminOpen" @click.outside="adminOpen = false" x-transition class="absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg py-1 min-w-44 z-50">
                            <a href="{{ route('topics.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Топіки</a>
                            <a href="{{ route('devices.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Пристрої</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Користувачі</a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="hidden md:flex items-center gap-3">
                <span class="text-sm text-gray-500">
                    {{ auth()->user()->name }}
                    @if(auth()->user()->isAdmin())
                        <span class="ml-1 px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs rounded font-medium">admin</span>
                    @elseif(auth()->user()->topic)
                        <span class="ml-1 px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded font-medium">{{ auth()->user()->topic->name }}</span>
                    @endif
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-sm text-gray-500 hover:text-red-600 transition">Вийти</button>
                </form>
            </div>
            <button @click="open = !open" class="md:hidden p-2 rounded-md text-gray-500 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
    <div x-show="open" x-transition class="md:hidden border-t border-gray-200 px-4 py-3 space-y-1">
        <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-100">Дашборд</a>
        <a href="{{ route('statistics') }}" class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-100">Статистика</a>
        <a href="{{ route('stores.index') }}" class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-100">Магазини</a>
        <a href="{{ route('sessions.index') }}" class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-100">Сесії</a>
        @if(auth()->user()->isAdmin())
            <a href="{{ route('topics.index') }}" class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-100">Топіки</a>
            <a href="{{ route('devices.index') }}" class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-100">Пристрої</a>
            <a href="{{ route('users.index') }}" class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-100">Користувачі</a>
        @endif
        <div class="pt-2 border-t border-gray-200">
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="block px-3 py-2 text-sm text-red-600 hover:bg-gray-100 w-full text-left rounded-md">Вийти</button>
            </form>
        </div>
    </div>
</nav>
