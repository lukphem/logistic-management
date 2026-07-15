<?php

use App\Http\Middleware\CheckIpWhitelist;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — single backend, consumed by web (Blade), mobile, and
| external integrators. Versioned from day one.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Auth (shared login endpoint, returns role/user_type-scoped token) ──
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

    // ── Core Staff — Sanctum + spatie permission checks ──
    Route::middleware(['auth:sanctum', 'user_type:staff'])->prefix('staff')->group(function () {
        Route::apiResource('roles', \App\Http\Controllers\Api\RoleController::class);
        Route::apiResource('permissions', \App\Http\Controllers\Api\PermissionController::class)->only(['index']);
        Route::apiResource('shipments', \App\Http\Controllers\Api\ShipmentController::class);
        Route::apiResource('rates', \App\Http\Controllers\Api\RateController::class);
        Route::get('/reports/exceptions', [\App\Http\Controllers\Api\ReportController::class, 'exceptions']);
    });

    // ── Riders/Drivers ──
    Route::middleware(['auth:sanctum', 'user_type:rider'])->prefix('rider')->group(function () {
        Route::get('/assigned-orders', [\App\Http\Controllers\Api\RiderController::class, 'assignedOrders']);
        Route::post('/scan', [\App\Http\Controllers\Api\RiderController::class, 'scan']);
        Route::post('/update-status', [\App\Http\Controllers\Api\RiderController::class, 'updateStatus']);
        Route::post('/location', [\App\Http\Controllers\Api\RiderController::class, 'pingLocation']);
        Route::post('/cod-remit', [\App\Http\Controllers\Api\RiderController::class, 'remitCod']);
        Route::get('/earnings', [\App\Http\Controllers\Api\RiderController::class, 'earnings']);
    });

    // ── Client Self-Service Portal (JWT/session — same logic as external group below) ──
    Route::middleware(['auth:sanctum', 'user_type:client'])->prefix('client')->group(function () {
        Route::post('/quote', [\App\Http\Controllers\Api\ClientController::class, 'quote']);
        Route::apiResource('shipments', \App\Http\Controllers\Api\ClientShipmentController::class);
        Route::get('/shipments/{id}/track', [\App\Http\Controllers\Api\ClientShipmentController::class, 'track']);
        Route::get('/invoices', [\App\Http\Controllers\Api\ClientController::class, 'invoices']);
        Route::get('/wallet', [\App\Http\Controllers\Api\ClientController::class, 'wallet']);
    });

    // ── External Client Integration (API key + IP whitelist, no session) ──
    Route::middleware([CheckIpWhitelist::class])->prefix('integration')->group(function () {
        Route::post('/quote', [\App\Http\Controllers\Api\ClientController::class, 'quote']);
        Route::post('/shipments', [\App\Http\Controllers\Api\ClientShipmentController::class, 'store']);
        Route::get('/shipments/{id}/track', [\App\Http\Controllers\Api\ClientShipmentController::class, 'track']);
        Route::post('/shipments/{id}/cancel', [\App\Http\Controllers\Api\ClientShipmentController::class, 'cancel']);
        Route::post('/webhooks/subscribe', [\App\Http\Controllers\Api\WebhookController::class, 'subscribe']);
    });
});
