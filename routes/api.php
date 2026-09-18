<?php

use App\Http\Middleware\CheckIpWhitelist;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — single backend, consumed by web (Blade), mobile, and
| external integrators. Versioned from day one.
|
| Every route in this file is named under the "api." prefix
| (Route::name('api.') below) — Route::apiResource() auto-generates
| names like "shipments.store"/"roles.store" with NO prefix by
| default, and routes/web.php happens to use those exact same names
| for its own, completely different routes. Without this prefix, the
| last-registered one silently wins name resolution: a web Blade form
| calling route('shipments.store') would resolve to this file's API
| URL instead, submitting to a Sanctum-token-protected JSON endpoint
| a browser session was never going to authenticate against — every
| web submission would 302 somewhere nonsensical with no error
| visible anywhere in the web app's own code, which is exactly what
| was happening before this line existed.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.')->group(function () {

    // ── Auth (shared login endpoint, returns role/user_type-scoped token) ──
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

    // ── Core Staff — Sanctum + spatie permission checks ──
    Route::middleware(['auth:sanctum', 'user_type:staff'])->prefix('staff')->group(function () {
        Route::apiResource('roles', \App\Http\Controllers\Api\RoleController::class);
        Route::apiResource('permissions', \App\Http\Controllers\Api\PermissionController::class)->only(['index']);
        Route::apiResource('shipments', \App\Http\Controllers\Api\ShipmentController::class);
        Route::get('/reports/exceptions', [\App\Http\Controllers\Api\ReportController::class, 'exceptions']);

        // ── Manifests — same ManifestService the staff web app uses,
        // so a trip/manifest created or dispatched from a mobile
        // device behaves identically to one done from a desktop. ──
        Route::get('/manifest-trips', [\App\Http\Controllers\Api\ManifestController::class, 'index'])->name('manifest-trips.index');
        Route::post('/manifest-trips', [\App\Http\Controllers\Api\ManifestController::class, 'store'])->name('manifest-trips.store');
        Route::get('/manifest-trips/{trip}', [\App\Http\Controllers\Api\ManifestController::class, 'show'])->name('manifest-trips.show');
        Route::post('/manifest-trips/{trip}/manifests', [\App\Http\Controllers\Api\ManifestController::class, 'storeManifest'])->name('manifest-trips.manifests.store');
        Route::post('/manifest-trips/{trip}/dispatch', [\App\Http\Controllers\Api\ManifestController::class, 'dispatch'])->name('manifest-trips.dispatch');
        Route::post('/manifests/{manifest}/receive', [\App\Http\Controllers\Api\ManifestController::class, 'receive'])->name('manifests.receive');
        Route::get('/manifest-shipments/eligible', [\App\Http\Controllers\Api\ManifestController::class, 'eligibleShipments'])->name('manifest-shipments.eligible');
        Route::post('/manifest-shipments/lookup', [\App\Http\Controllers\Api\ManifestController::class, 'lookupByTrackingNumber'])->name('manifest-shipments.lookup');
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
        Route::post('/shipments/{id}/cancel', [\App\Http\Controllers\Api\ClientShipmentController::class, 'cancel']);
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
