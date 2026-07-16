<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\CityController;
use App\Http\Controllers\Web\ClientBillingController;
use App\Http\Controllers\Web\CountryController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DistrictController;
use App\Http\Controllers\Web\HubController;
use App\Http\Controllers\Web\OnforwardingClassificationController;
use App\Http\Controllers\Web\OutletController;
use App\Http\Controllers\Web\RateCardController;
use App\Http\Controllers\Web\RegionController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\ScanStatusController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\ShipmentController;
use App\Http\Controllers\Web\StateController;
use App\Http\Controllers\Web\UnitController;
use App\Http\Controllers\Web\UserController;
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

    // Location setup (regions, hubs/branches, outlets, zones) — gated per-action.
    Route::middleware('can:locations:read')->group(function () {
        Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
        Route::get('/states', [StateController::class, 'index'])->name('states.index');
        Route::get('/cities', [CityController::class, 'index'])->name('cities.index');
        Route::get('/districts', [DistrictController::class, 'index'])->name('districts.index');
        Route::get('/regions', [RegionController::class, 'index'])->name('regions.index');
        Route::get('/hubs', [HubController::class, 'index'])->name('hubs.index');
        Route::get('/outlets', [OutletController::class, 'index'])->name('outlets.index');
        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::get('/zones', [ZoneController::class, 'index'])->name('zones.index');
    });
    Route::middleware('can:locations:create')->group(function () {
        Route::get('/countries/create', [CountryController::class, 'create'])->name('countries.create');
        Route::post('/countries', [CountryController::class, 'store'])->name('countries.store');
        Route::get('/states/create', [StateController::class, 'create'])->name('states.create');
        Route::post('/states', [StateController::class, 'store'])->name('states.store');
        Route::get('/cities/create', [CityController::class, 'create'])->name('cities.create');
        Route::post('/cities', [CityController::class, 'store'])->name('cities.store');
        Route::get('/districts/create', [DistrictController::class, 'create'])->name('districts.create');
        Route::post('/districts', [DistrictController::class, 'store'])->name('districts.store');
        Route::get('/regions/create', [RegionController::class, 'create'])->name('regions.create');
        Route::post('/regions', [RegionController::class, 'store'])->name('regions.store');
        Route::get('/hubs/create', [HubController::class, 'create'])->name('hubs.create');
        Route::post('/hubs', [HubController::class, 'store'])->name('hubs.store');
        Route::get('/outlets/create', [OutletController::class, 'create'])->name('outlets.create');
        Route::post('/outlets', [OutletController::class, 'store'])->name('outlets.store');
        Route::get('/units/create', [UnitController::class, 'create'])->name('units.create');
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::get('/zones/create', [ZoneController::class, 'create'])->name('zones.create');
        Route::post('/zones', [ZoneController::class, 'store'])->name('zones.store');
    });
    Route::middleware('can:locations:update')->group(function () {
        Route::get('/countries/{country}/edit', [CountryController::class, 'edit'])->name('countries.edit');
        Route::put('/countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::get('/states/{state}/edit', [StateController::class, 'edit'])->name('states.edit');
        Route::put('/states/{state}', [StateController::class, 'update'])->name('states.update');
        Route::get('/cities/{city}/edit', [CityController::class, 'edit'])->name('cities.edit');
        Route::put('/cities/{city}', [CityController::class, 'update'])->name('cities.update');
        Route::get('/districts/{district}/edit', [DistrictController::class, 'edit'])->name('districts.edit');
        Route::put('/districts/{district}', [DistrictController::class, 'update'])->name('districts.update');
        Route::get('/regions/{region}/edit', [RegionController::class, 'edit'])->name('regions.edit');
        Route::put('/regions/{region}', [RegionController::class, 'update'])->name('regions.update');
        Route::get('/hubs/{hub}/edit', [HubController::class, 'edit'])->name('hubs.edit');
        Route::put('/hubs/{hub}', [HubController::class, 'update'])->name('hubs.update');
        Route::get('/outlets/{outlet}/edit', [OutletController::class, 'edit'])->name('outlets.edit');
        Route::put('/outlets/{outlet}', [OutletController::class, 'update'])->name('outlets.update');
        Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
        Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::get('/zones/{zone}/edit', [ZoneController::class, 'edit'])->name('zones.edit');
        Route::put('/zones/{zone}', [ZoneController::class, 'update'])->name('zones.update');
    });
    Route::middleware('can:locations:delete')->group(function () {
        Route::delete('/countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');
        Route::delete('/states/{state}', [StateController::class, 'destroy'])->name('states.destroy');
        Route::delete('/cities/{city}', [CityController::class, 'destroy'])->name('cities.destroy');
        Route::delete('/districts/{district}', [DistrictController::class, 'destroy'])->name('districts.destroy');
        Route::delete('/regions/{region}', [RegionController::class, 'destroy'])->name('regions.destroy');
        Route::delete('/hubs/{hub}', [HubController::class, 'destroy'])->name('hubs.destroy');
        Route::delete('/outlets/{outlet}', [OutletController::class, 'destroy'])->name('outlets.destroy');
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
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
        Route::get('/onforwarding-classifications', [OnforwardingClassificationController::class, 'index'])->name('onforwarding-classifications.index');
    });
    Route::middleware('can:billing:update')->group(function () {
        Route::put('/client-billing/{type}/{id}', [ClientBillingController::class, 'update'])->name('client-billing.update');
        Route::get('/onforwarding-classifications/create', [OnforwardingClassificationController::class, 'create'])->name('onforwarding-classifications.create');
        Route::post('/onforwarding-classifications', [OnforwardingClassificationController::class, 'store'])->name('onforwarding-classifications.store');
        Route::get('/onforwarding-classifications/{onforwardingClassification}/edit', [OnforwardingClassificationController::class, 'edit'])->name('onforwarding-classifications.edit');
        Route::put('/onforwarding-classifications/{onforwardingClassification}', [OnforwardingClassificationController::class, 'update'])->name('onforwarding-classifications.update');
        Route::delete('/onforwarding-classifications/{onforwardingClassification}', [OnforwardingClassificationController::class, 'destroy'])->name('onforwarding-classifications.destroy');
    });

    // Staff user management + roles/permissions.
    Route::middleware('can:users:read')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
    Route::middleware('can:users:create')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });
    Route::middleware('can:users:update')->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/status/{status}', [UserController::class, 'changeStatus'])
            ->whereIn('status', ['active', 'suspended', 'locked', 'terminated'])
            ->name('users.change-status');
    });
    Route::middleware('can:users:delete')->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('can:roles:read')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    });
    Route::middleware('can:roles:create')->group(function () {
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    });
    Route::middleware('can:roles:update')->group(function () {
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
    Route::middleware('can:roles:delete')->group(function () {
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
});
