<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // ── Доступно всім авторизованим ───────────────────────────────
    Route::get('/dashboard',  [DashboardController::class,  'index'])->name('dashboard');
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');

    Route::get('/stores',          [StoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/{store}',  [StoreController::class, 'show'])->name('stores.show');

    Route::get('/sessions/stores',     [SessionController::class, 'storeSessions'])->name('sessions.store');
    Route::get('/sessions/generators', [SessionController::class, 'generatorSessions'])->name('sessions.generator');

    // ── Тільки для адміна ─────────────────────────────────────────
    Route::middleware('admin')->group(function () {

        // Магазини (CRUD)
        Route::get('/stores/create',          [StoreController::class, 'create'])->name('stores.create');
        Route::post('/stores',                [StoreController::class, 'store'])->name('stores.store');
        Route::get('/stores/{store}/edit',    [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('/stores/{store}',         [StoreController::class, 'update'])->name('stores.update');
        Route::delete('/stores/{store}',      [StoreController::class, 'destroy'])->name('stores.destroy');

        // Топіки
        Route::resource('topics', TopicController::class);

        // Пристрої
        Route::resource('devices', DeviceController::class)->except('show');

        // Юзери
        Route::resource('users', UserController::class);

        // Сесії магазинів (CRUD)
        Route::get('/sessions/stores/create',         [SessionController::class, 'createStoreSession'])->name('sessions.store.create');
        Route::post('/sessions/stores',               [SessionController::class, 'storeStoreSession'])->name('sessions.store.store');
        Route::get('/sessions/stores/{session}/edit', [SessionController::class, 'editStoreSession'])->name('sessions.store.edit');
        Route::put('/sessions/stores/{session}',      [SessionController::class, 'updateStoreSession'])->name('sessions.store.update');
        Route::delete('/sessions/stores/{session}',   [SessionController::class, 'destroyStoreSession'])->name('sessions.store.destroy');

        // Сесії генератора (CRUD)
        Route::get('/sessions/generators/create',         [SessionController::class, 'createGeneratorSession'])->name('sessions.generator.create');
        Route::post('/sessions/generators',               [SessionController::class, 'storeGeneratorSession'])->name('sessions.generator.store');
        Route::get('/sessions/generators/{session}/edit', [SessionController::class, 'editGeneratorSession'])->name('sessions.generator.edit');
        Route::put('/sessions/generators/{session}',      [SessionController::class, 'updateGeneratorSession'])->name('sessions.generator.update');
        Route::delete('/sessions/generators/{session}',   [SessionController::class, 'destroyGeneratorSession'])->name('sessions.generator.destroy');
    });
});

require __DIR__ . '/auth.php';
