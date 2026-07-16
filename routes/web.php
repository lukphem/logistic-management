<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HubController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\ShipmentController;
use App\Http\Controllers\Web\ZoneController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');

    // System setup — gated to whoever holds settings:update (Super Admin, Finance-read only sees nothing here).
    Route::middleware('can:settings:update')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    // Location setup (hubs/branches, zones/regions) — gated per-action.
    Route::middleware('can:locations:read')->group(function () {
        Route::get('/hubs', [HubController::class, 'index'])->name('hubs.index');
        Route::get('/zones', [ZoneController::class, 'index'])->name('zones.index');
    });
    Route::middleware('can:locations:create')->group(function () {
        Route::get('/hubs/create', [HubController::class, 'create'])->name('hubs.create');
        Route::post('/hubs', [HubController::class, 'store'])->name('hubs.store');
        Route::get('/zones/create', [ZoneController::class, 'create'])->name('zones.create');
        Route::post('/zones', [ZoneController::class, 'store'])->name('zones.store');
    });
    Route::middleware('can:locations:update')->group(function () {
        Route::get('/hubs/{hub}/edit', [HubController::class, 'edit'])->name('hubs.edit');
        Route::put('/hubs/{hub}', [HubController::class, 'update'])->name('hubs.update');
        Route::get('/zones/{zone}/edit', [ZoneController::class, 'edit'])->name('zones.edit');
        Route::put('/zones/{zone}', [ZoneController::class, 'update'])->name('zones.update');
    });
    Route::middleware('can:locations:delete')->group(function () {
        Route::delete('/hubs/{hub}', [HubController::class, 'destroy'])->name('hubs.destroy');
        Route::delete('/zones/{zone}', [ZoneController::class, 'destroy'])->name('zones.destroy');
    });
});
