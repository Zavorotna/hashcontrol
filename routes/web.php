<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::middleware('auth')->get('/topics/{topic}/stores', function (App\Models\Topic $topic) {
    return $topic->stores()->where('is_active', true)->get(['id', 'name', 'mqtt_device_id']);
});
Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Доступно всім авторизованим ───────────────────────────────
    Route::get('/dashboard',  [DashboardController::class,  'index'])->name('dashboard');
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');

    Route::get('/stores',         [StoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/{store}', [StoreController::class, 'show'])->name('stores.show');

    // Сесії — перегляд
    Route::get('/sessions',         [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}',[SessionController::class, 'show'])->name('sessions.show');

    // ── Тільки для адміна ─────────────────────────────────────────
    Route::middleware('admin')->group(function () {

        // Магазини (CRUD)
        Route::get('/stores/create',       [StoreController::class, 'create'])->name('stores.create');
        Route::post('/stores',             [StoreController::class, 'store'])->name('stores.store');
        Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('/stores/{store}',      [StoreController::class, 'update'])->name('stores.update');
        Route::delete('/stores/{store}',   [StoreController::class, 'destroy'])->name('stores.destroy');

        // Топіки
        Route::resource('topics', TopicController::class);

        // Пристрої
        Route::resource('devices', DeviceController::class);

        // Юзери
        Route::resource('users', UserController::class);

        // Сесії — редагування (тільки адмін)
        Route::get('/sessions/{session}/edit', [SessionController::class, 'edit'])->name('sessions.edit');
        Route::put('/sessions/{session}',      [SessionController::class, 'update'])->name('sessions.update');
        Route::delete('/sessions/{session}',   [SessionController::class, 'destroy'])->name('sessions.destroy');
    });
});

require __DIR__.'/auth.php';