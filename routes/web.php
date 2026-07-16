<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\ClientBillingController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HubController;
use App\Http\Controllers\Web\RateCardController;
use App\Http\Controllers\Web\ScanStatusController;
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

        Route::get('/scan-statuses', [ScanStatusController::class, 'index'])->name('scan-statuses.index');
        Route::post('/scan-statuses', [ScanStatusController::class, 'store'])->name('scan-statuses.store');
        Route::put('/scan-statuses/{scanStatus}', [ScanStatusController::class, 'update'])->name('scan-statuses.update');
        Route::delete('/scan-statuses/{scanStatus}', [ScanStatusController::class, 'destroy'])->name('scan-statuses.destroy');
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

    // Billing setup: rate cards (the standard rate) + per-client standard/special assignment.
    Route::middleware('can:rates:read')->group(function () {
        Route::get('/rate-cards', [RateCardController::class, 'index'])->name('rate-cards.index');
    });
    Route::middleware('can:rates:create')->group(function () {
        Route::get('/rate-cards/create', [RateCardController::class, 'create'])->name('rate-cards.create');
        Route::post('/rate-cards', [RateCardController::class, 'store'])->name('rate-cards.store');
    });
    Route::middleware('can:rates:update')->group(function () {
        Route::get('/rate-cards/{rateCard}/edit', [RateCardController::class, 'edit'])->name('rate-cards.edit');
        Route::put('/rate-cards/{rateCard}', [RateCardController::class, 'update'])->name('rate-cards.update');
        Route::post('/rate-cards/{rateCard}/zone-prices', [RateCardController::class, 'setZonePrice'])->name('rate-cards.zone-prices.store');
        Route::delete('/rate-cards/{rateCard}/zone-prices/{zonePrice}', [RateCardController::class, 'destroyZonePrice'])->name('rate-cards.zone-prices.destroy');
    });
    Route::middleware('can:rates:delete')->group(function () {
        Route::delete('/rate-cards/{rateCard}', [RateCardController::class, 'destroy'])->name('rate-cards.destroy');
    });

    Route::middleware('can:billing:read')->group(function () {
        Route::get('/client-billing', [ClientBillingController::class, 'index'])->name('client-billing.index');
        Route::get('/client-billing/{type}/{id}/edit', [ClientBillingController::class, 'edit'])->name('client-billing.edit');
    });
    Route::middleware('can:billing:update')->group(function () {
        Route::put('/client-billing/{type}/{id}', [ClientBillingController::class, 'update'])->name('client-billing.update');
    });
});
