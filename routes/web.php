<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\TrackedObjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'admin') {
            return redirect('/admin');
        }
        return redirect('/user');
    });

    Route::prefix('admin')->middleware('can:admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.index');
        Route::get('/register-device/{id}', [AdminController::class, 'showRegisterDeviceForm'])->name('admin.registerDevice.form');
        Route::post('/register-device/{id}', [AdminController::class, 'registerDevice'])->name('admin.registerDevice');

        // MQTT requests management
        Route::delete('/requests/{id}', [AdminController::class, 'destroyMqttMessage'])->name('admin.requests.destroy');
        Route::post('/requests/{id}/restore', [AdminController::class, 'restoreMqttMessage'])->name('admin.requests.restore');

        // Devices
        Route::get('/devices', [AdminController::class, 'devices'])->name('admin.devices');
        Route::get('/devices/create', [AdminController::class, 'createDevice'])->name('admin.devices.create');
        Route::post('/devices', [AdminController::class, 'storeDevice'])->name('admin.devices.store');
        Route::get('/devices/{device}/edit', [AdminController::class, 'editDevice'])->name('admin.devices.edit');
        Route::put('/devices/{device}', [AdminController::class, 'updateDevice'])->name('admin.devices.update');
        Route::delete('/devices/{device}', [AdminController::class, 'destroyDevice'])->name('admin.devices.destroy');
        Route::post('/devices/{device}/restore', [AdminController::class, 'restoreDevice'])->name('admin.devices.restore');

        // Device actions
        Route::post('/devices/{device}/actions', [AdminController::class, 'addActionToDevice'])->name('admin.devices.actions.store');
        Route::delete('/devices/{device}/actions/{deviceAction}', [AdminController::class, 'removeActionFromDevice'])->name('admin.devices.actions.destroy');

        // Actions
        Route::get('/actions', [AdminController::class, 'actions'])->name('admin.actions');
        Route::get('/actions/create', [AdminController::class, 'createAction'])->name('admin.actions.create');
        Route::post('/actions', [AdminController::class, 'storeAction'])->name('admin.actions.store');
        Route::get('/actions/{action}/edit', [AdminController::class, 'editAction'])->name('admin.actions.edit');
        Route::put('/actions/{action}', [AdminController::class, 'updateAction'])->name('admin.actions.update');
        Route::delete('/actions/{action}', [AdminController::class, 'destroyAction'])->name('admin.actions.destroy');

        // Companies
        Route::get('/companies', [AdminController::class, 'companies'])->name('admin.companies');
        Route::get('/companies/create', [AdminController::class, 'createCompany'])->name('admin.companies.create');
        Route::post('/companies', [AdminController::class, 'storeCompany'])->name('admin.companies.store');
        Route::get('/companies/{company}/edit', [AdminController::class, 'editCompany'])->name('admin.companies.edit');
        Route::put('/companies/{company}', [AdminController::class, 'updateCompany'])->name('admin.companies.update');
        Route::delete('/companies/{company}', [AdminController::class, 'destroyCompany'])->name('admin.companies.destroy');

        // Offices
        Route::get('/offices', [AdminController::class, 'offices'])->name('admin.offices');
        Route::get('/offices/create', [AdminController::class, 'createOffice'])->name('admin.offices.create');
        Route::post('/offices', [AdminController::class, 'storeOffice'])->name('admin.offices.store');
        Route::get('/offices/{office}/edit', [AdminController::class, 'editOffice'])->name('admin.offices.edit');
        Route::put('/offices/{office}', [AdminController::class, 'updateOffice'])->name('admin.offices.update');
        Route::delete('/offices/{office}', [AdminController::class, 'destroyOffice'])->name('admin.offices.destroy');

        // Users
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/{user}/dashboard', [AdminController::class, 'userDashboard'])->name('admin.users.dashboard');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');

        // Blacklist
        Route::get('/blacklist', [AdminController::class, 'blacklistedDevices'])->name('admin.blacklisted_devices');
        Route::get('/blacklist/create', [AdminController::class, 'createBlacklistedDevice'])->name('admin.blacklisted_devices.create');
        Route::post('/blacklist', [AdminController::class, 'storeBlacklistedDevice'])->name('admin.blacklisted_devices.store');
        Route::get('/blacklist/{blacklistedDevice}/edit', [AdminController::class, 'editBlacklistedDevice'])->name('admin.blacklisted_devices.edit');
        Route::put('/blacklist/{blacklistedDevice}', [AdminController::class, 'updateBlacklistedDevice'])->name('admin.blacklisted_devices.update');
        Route::delete('/blacklist/{blacklistedDevice}', [AdminController::class, 'destroyBlacklistedDevice'])->name('admin.blacklisted_devices.destroy');
        Route::post('/blacklist/{id}/restore', [AdminController::class, 'restoreBlacklistedDevice'])->name('admin.blacklisted_devices.restore');
    });

    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('user.index');
        Route::get('/tracked-objects',                       [TrackedObjectController::class, 'index'])->name('user.tracked-objects.index');
        Route::get('/tracked-objects/create',                [TrackedObjectController::class, 'create'])->name('user.tracked-objects.create');
        Route::post('/tracked-objects',                      [TrackedObjectController::class, 'store'])->name('user.tracked-objects.store');
        Route::get('/tracked-objects/{trackedObject}',             [TrackedObjectController::class, 'show'])->name('user.tracked-objects.show');
        Route::post('/tracked-objects/{trackedObject}/send-command', [TrackedObjectController::class, 'sendCommand'])->name('user.tracked-objects.send-command');
        Route::get('/tracked-objects/{trackedObject}/edit',        [TrackedObjectController::class, 'edit'])->name('user.tracked-objects.edit');
        Route::put('/tracked-objects/{trackedObject}',             [TrackedObjectController::class, 'update'])->name('user.tracked-objects.update');
        Route::delete('/tracked-objects/{trackedObject}',          [TrackedObjectController::class, 'destroy'])->name('user.tracked-objects.destroy');

        Route::get('/settings',         [UserController::class, 'settings'])->name('user.settings');
        Route::post('/settings/password',[UserController::class, 'updatePassword'])->name('user.settings.password');
    });
});
