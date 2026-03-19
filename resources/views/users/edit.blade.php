<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Редагувати: {{ $user->name }}</h1>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('users.update', $user) }}"
                  class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                @csrf @method('PUT')

                {{-- Ім'я + Email --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ім'я *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                {{-- Пароль --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Новий пароль
                            <span class="text-gray-400 font-normal">(залиш порожнім щоб не змінювати)</span>
                        </label>
                        <input type="password" name="password"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Підтвердження</label>
                        <input type="password" name="password_confirmation"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                {{-- Роль + Топік --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Роль *</label>
                        <select name="role" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="user"  {{ old('role', $user->role) === 'user'  ? 'selected' : '' }}>User</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Топік (локація)</label>
                        <select name="topic_id" onchange="updateStoreList(this.value)"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— не прив'язаний —</option>
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}"
                                    {{ old('topic_id', $user->topic_id) == $topic->id ? 'selected' : '' }}>
                                    {{ $topic->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Магазини топіку (інфо) --}}
                <div id="storesInfo">
                    @if($topicStores->isNotEmpty())
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                        <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-2">
                            Магазини що будуть доступні ({{ $topicStores->count() }}):
                        </p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($topicStores as $store)
                                <span class="text-xs bg-white border border-blue-200 text-blue-700 px-2 py-0.5 rounded">
                                    {{ $store->name }}
                                </span>
                            @endforeach
                        </div>
                        <p class="text-xs text-blue-500 mt-2">
                            При збереженні юзер отримає доступ до всіх магазинів цього топіку.
                        </p>
                    </div>
                    @else
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-400 text-center">
                        Оберіть топік щоб побачити магазини
                    </div>
                    @endif
                </div>

                {{-- Віджети --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Віджети дашборду</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach(\App\Models\User::WIDGETS as $key => $label)
                            <label class="flex items-center gap-2 cursor-pointer border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">
                                <input type="checkbox" name="dashboard_widgets[]" value="{{ $key }}"
                                       {{ in_array($key, old('dashboard_widgets', $user->activeWidgets())) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-300 text-blue-600">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        Зберегти зміни
                    </button>
                    <a href="{{ route('users.index') }}"
                       class="px-5 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                        Скасувати
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function updateStoreList(topicId) {
        const box = document.getElementById('storesInfo');
        if (!topicId) {
            box.innerHTML = '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-400 text-center">Оберіть топік щоб побачити магазини</div>';
            return;
        }

        box.innerHTML = '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-400 text-center">Завантаження...</div>';

        fetch(`/api/topics/${topicId}/stores`)
            .then(r => r.json())
            .then(stores => {
                if (!stores.length) {
                    box.innerHTML = '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-400 text-center">Магазинів у цьому топіку немає</div>';
                    return;
                }
                const tags = stores.map(s =>
                    `<span class="text-xs bg-white border border-blue-200 text-blue-700 px-2 py-0.5 rounded">${s.name}</span>`
                ).join('');
                box.innerHTML = `
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                        <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-2">Магазини що будуть доступні (${stores.length}):</p>
                        <div class="flex flex-wrap gap-1">${tags}</div>
                        <p class="text-xs text-blue-500 mt-2">При збереженні юзер отримає доступ до всіх магазинів цього топіку.</p>
                    </div>`;
            });
    }
    </script>
</x-app-layout>