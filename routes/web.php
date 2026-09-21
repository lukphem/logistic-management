<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\AdditionalServiceController;
use App\Http\Controllers\Web\BulkShipmentController;
use App\Http\Controllers\Web\CityController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\CountryController;
use App\Http\Controllers\Web\CountryRegionController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DeliveryScanController;
use App\Http\Controllers\Web\DistrictController;
use App\Http\Controllers\Web\FleetBillingTariffController;
use App\Http\Controllers\Web\HubController;
use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\Web\OnforwardingClassificationController;
use App\Http\Controllers\Web\OriginDestinationTariffController;
use App\Http\Controllers\Web\OutletController;
use App\Http\Controllers\Web\QuoteController;
use App\Http\Controllers\Web\RateCheckerController;
use App\Http\Controllers\Web\RegionController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\RouteController;
use App\Http\Controllers\Web\ScanStatusController;
use App\Http\Controllers\Web\ServiceTypeController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\PrintDocumentController;
use App\Http\Controllers\Web\ManifestController;
use App\Http\Controllers\Web\ManifestTripController;
use App\Http\Controllers\Web\OperationalScanController;
use App\Http\Controllers\Web\PaymentReportController;
use App\Http\Controllers\Web\ReconciliationController;
use App\Http\Controllers\Web\ShipmentController;
use App\Http\Controllers\Web\StandardBillingController;
use App\Http\Controllers\Web\StateController;
use App\Http\Controllers\Web\TerritoryController;
use App\Http\Controllers\Web\UnitController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\VehicleTypeController;
use App\Http\Controllers\Web\StaffTrackingController;
use App\Http\Controllers\Web\TrackingController;
use App\Http\Controllers\Web\ZoneController;
use App\Http\Controllers\Web\ZoneMappingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Public — no login, same "type a tracking number" page every
// courier's own website has. Deliberately placed outside every
// auth/staff/guest middleware group below, since it isn't gated on
// having an account at all, staff or otherwise.
Route::get('/track', [TrackingController::class, 'search'])->name('tracking.search');
Route::post('/track', [TrackingController::class, 'submit'])->name('tracking.submit');
Route::get('/track/multi', [TrackingController::class, 'multi'])->name('tracking.multi');
Route::get('/track/{trackingNumber}', [TrackingController::class, 'show'])->name('tracking.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/staff-tracking', [StaffTrackingController::class, 'search'])->name('staff-tracking.search');
    Route::post('/staff-tracking', [StaffTrackingController::class, 'submit'])->name('staff-tracking.submit');
    Route::get('/staff-tracking/multi', [StaffTrackingController::class, 'multi'])->name('staff-tracking.multi');
    Route::get('/staff-tracking/{trackingNumber}', [StaffTrackingController::class, 'show'])->name('staff-tracking.show');

    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::middleware('can:shipments:create')->group(function () {
        Route::get('/shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::post('/shipments/preview-price', [ShipmentController::class, 'previewPrice'])->name('shipments.preview-price');
        Route::get('/shipments/account-billing-options', [ShipmentController::class, 'accountBillingOptions'])->name('shipments.account-billing-options');
        Route::get('/quotes/{quoteNumber}', [QuoteController::class, 'show'])->name('quotes.show');
        Route::get('/shipments/bulk/create', [BulkShipmentController::class, 'create'])->name('shipments.bulk.create');
        Route::get('/shipments/bulk/template', [BulkShipmentController::class, 'downloadTemplate'])->name('shipments.bulk.template');
        Route::post('/shipments/bulk/preview', [BulkShipmentController::class, 'preview'])->name('shipments.bulk.preview');
        Route::post('/shipments/bulk', [BulkShipmentController::class, 'store'])->name('shipments.bulk.store');
    });
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::get('/shipments/{shipment}/label', [ShipmentController::class, 'label'])->name('shipments.label');
    Route::get('/shipments/{shipment}/waybill', [ShipmentController::class, 'waybillDocument'])->name('shipments.waybill');
    Route::get('/shipments/{shipment}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
    Route::get('/payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');
    Route::get('/reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::post('/reconciliation', [ReconciliationController::class, 'store'])->name('reconciliation.store');
    Route::get('/cash-settlements/{settlement}/pay', [PaymentController::class, 'paySettlement'])->name('payments.pay-settlement');
    Route::post('/payments/check-status', [PaymentController::class, 'checkStatus'])->name('payments.check-status');
    Route::middleware('can:payments:read')->group(function () {
        Route::get('/payment-reports', [PaymentReportController::class, 'index'])->name('payment-reports.index');
    });
    Route::middleware('can:manifests:create')->group(function () {
        Route::get('/manifest-trips/create', [ManifestTripController::class, 'create'])->name('manifest-trips.create');
        Route::post('/manifest-trips', [ManifestTripController::class, 'store'])->name('manifest-trips.store');
        Route::get('/manifest-trips/{trip}/manifests/create', [ManifestController::class, 'create'])->name('manifests.create');
        Route::post('/manifest-trips/{trip}/manifests', [ManifestController::class, 'store'])->name('manifests.store');
        Route::get('/manifests/{manifest}/edit', [ManifestController::class, 'edit'])->name('manifests.edit');
        Route::post('/manifests/{manifest}/shipments', [ManifestController::class, 'addShipments'])->name('manifests.add-shipments');
        Route::delete('/manifests/{manifest}/shipments/{shipment}', [ManifestController::class, 'removeShipment'])->name('manifests.remove-shipment');
    });
    // Registered here (before the /manifest-trips/{trip} wildcard
    // further down) purely to avoid the exact route-shadowing risk
    // fixed earlier this session — "print" would otherwise be
    // captured as a {trip} value.
    Route::middleware('can:manifests:read')->group(function () {
        Route::get('/print-documents', [PrintDocumentController::class, 'search'])->name('print-documents.search');
        Route::post('/print-documents', [PrintDocumentController::class, 'lookup'])->name('print-documents.lookup');
    });
    Route::middleware('can:manifests:read')->group(function () {
        Route::get('/manifest-trips', [ManifestTripController::class, 'index'])->name('manifest-trips.index');
        Route::get('/manifest-trips/{trip}', [ManifestTripController::class, 'show'])->name('manifest-trips.show');
        Route::get('/manifest-trips/{trip}/print', [ManifestTripController::class, 'print'])->name('manifest-trips.print');
        Route::get('/manifests/{manifest}/print', [ManifestController::class, 'print'])->name('manifests.print');
        Route::get('/manifest-shipments/eligible', [ManifestTripController::class, 'eligibleShipments'])->name('manifests.eligible-shipments');
        Route::post('/manifest-shipments/lookup', [ManifestTripController::class, 'lookupByTrackingNumber'])->name('manifests.lookup-tracking-number');
    });
    Route::middleware('can:manifests:update')->group(function () {
        Route::post('/manifest-trips/{trip}/dispatch', [ManifestTripController::class, 'dispatch'])->name('manifest-trips.dispatch');
        Route::get('/manifests/{manifest}/receive', [ManifestController::class, 'receive'])->name('manifests.receive');
        Route::post('/manifests/{manifest}/receive', [ManifestController::class, 'storeReceive'])->name('manifests.store-receive');
    });
    Route::middleware('can:delivery-scan:update')->group(function () {
        Route::get('/operational-scans/delivery', [DeliveryScanController::class, 'index'])->name('operational-scans.delivery.index');
        Route::post('/operational-scans/delivery/lookup', [DeliveryScanController::class, 'lookup'])->name('operational-scans.delivery.lookup');
        Route::post('/operational-scans/delivery', [DeliveryScanController::class, 'store'])->name('operational-scans.delivery.store');
    });
    // The other five scan types share one route each (the {type}
    // wildcard), so a single can: middleware can't gate them
    // separately — each one's own permission (pickup-scan:update,
    // departure-scan:update, and so on) is instead checked inside
    // OperationalScanController itself, at runtime, based on which
    // type was actually requested.
    Route::get('/operational-scans/{type}', [OperationalScanController::class, 'index'])->name('operational-scans.index');
    Route::post('/operational-scans/{type}/lookup', [OperationalScanController::class, 'lookup'])->name('operational-scans.lookup');
    Route::post('/operational-scans/{type}', [OperationalScanController::class, 'store'])->name('operational-scans.store');
    Route::post('/operational-scans-evidence', [OperationalScanController::class, 'uploadEvidence'])->name('operational-scans.upload-evidence');
    Route::get('/operational-scans-nearby-destinations', [OperationalScanController::class, 'nearbyDestinations'])->name('operational-scans.nearby-destinations');
    Route::get('/operational-scans-print-transfer', [OperationalScanController::class, 'printTransfer'])->name('operational-scans.print-transfer');
    Route::get('/operational-scans-print-delivery-sheet', [OperationalScanController::class, 'printDeliverySheet'])->name('operational-scans.print-delivery-sheet');
    Route::middleware('can:shipments:update')->group(function () {
        Route::get('/shipments/{shipment}/edit', [ShipmentController::class, 'edit'])->name('shipments.edit');
        Route::put('/shipments/{shipment}', [ShipmentController::class, 'update'])->name('shipments.update');
    });

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
        Route::get('/territories', [TerritoryController::class, 'index'])->name('territories.index');
        Route::get('/country-regions', [CountryRegionController::class, 'index'])->name('country-regions.index');
        Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
        Route::get('/cities', [CityController::class, 'index'])->name('cities.index');
        Route::get('/districts', [DistrictController::class, 'index'])->name('districts.index');
        Route::get('/regions', [RegionController::class, 'index'])->name('regions.index');
        Route::get('/hubs', [HubController::class, 'index'])->name('hubs.index');
        Route::get('/outlets', [OutletController::class, 'index'])->name('outlets.index');
        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::get('/zones', [ZoneController::class, 'index'])->name('zones.index');

        Route::get('/countries/export', [CountryController::class, 'export'])->name('countries.export');
        Route::get('/states/export', [StateController::class, 'export'])->name('states.export');
        Route::get('/territories/export', [TerritoryController::class, 'export'])->name('territories.export');
        Route::get('/cities/export', [CityController::class, 'export'])->name('cities.export');
        Route::get('/districts/export', [DistrictController::class, 'export'])->name('districts.export');
        Route::get('/zones/export', [ZoneController::class, 'export'])->name('zones.export');
    });
    Route::middleware('can:locations:create')->group(function () {
        Route::get('/countries/create', [CountryController::class, 'create'])->name('countries.create');
        Route::post('/countries', [CountryController::class, 'store'])->name('countries.store');
        Route::get('/states/create', [StateController::class, 'create'])->name('states.create');
        Route::post('/states', [StateController::class, 'store'])->name('states.store');
        Route::get('/territories/create', [TerritoryController::class, 'create'])->name('territories.create');
        Route::post('/territories', [TerritoryController::class, 'store'])->name('territories.store');
        Route::get('/country-regions/create', [CountryRegionController::class, 'create'])->name('country-regions.create');
        Route::post('/country-regions', [CountryRegionController::class, 'store'])->name('country-regions.store');
        Route::get('/routes/create', [RouteController::class, 'create'])->name('routes.create');
        Route::post('/routes', [RouteController::class, 'store'])->name('routes.store');
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
        Route::get('/territories/{territory}/edit', [TerritoryController::class, 'edit'])->name('territories.edit');
        Route::put('/territories/{territory}', [TerritoryController::class, 'update'])->name('territories.update');
        Route::get('/country-regions/{countryRegion}/edit', [CountryRegionController::class, 'edit'])->name('country-regions.edit');
        Route::put('/country-regions/{countryRegion}', [CountryRegionController::class, 'update'])->name('country-regions.update');
        Route::get('/routes/{route}/edit', [RouteController::class, 'edit'])->name('routes.edit');
        Route::put('/routes/{route}', [RouteController::class, 'update'])->name('routes.update');
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

        Route::post('/countries/import', [CountryController::class, 'import'])->name('countries.import');
        Route::post('/states/import', [StateController::class, 'import'])->name('states.import');
        Route::post('/territories/import', [TerritoryController::class, 'import'])->name('territories.import');
        Route::post('/cities/import', [CityController::class, 'import'])->name('cities.import');
        Route::post('/districts/import', [DistrictController::class, 'import'])->name('districts.import');
        Route::post('/zones/import', [ZoneController::class, 'import'])->name('zones.import');
    });
    Route::middleware('can:locations:delete')->group(function () {
        Route::delete('/countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');
        Route::delete('/states/{state}', [StateController::class, 'destroy'])->name('states.destroy');
        Route::delete('/territories/{territory}', [TerritoryController::class, 'destroy'])->name('territories.destroy');
        Route::delete('/country-regions/{countryRegion}', [CountryRegionController::class, 'destroy'])->name('country-regions.destroy');
        Route::delete('/routes/{route}', [RouteController::class, 'destroy'])->name('routes.destroy');
        Route::delete('/cities/{city}', [CityController::class, 'destroy'])->name('cities.destroy');
        Route::delete('/districts/{district}', [DistrictController::class, 'destroy'])->name('districts.destroy');
        Route::delete('/regions/{region}', [RegionController::class, 'destroy'])->name('regions.destroy');
        Route::delete('/hubs/{hub}', [HubController::class, 'destroy'])->name('hubs.destroy');
        Route::delete('/outlets/{outlet}', [OutletController::class, 'destroy'])->name('outlets.destroy');
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
        Route::delete('/zones/{zone}', [ZoneController::class, 'destroy'])->name('zones.destroy');
    });

    // Billing setup: zone mapping (rate cards being rebuilt one billing model at a time).
    Route::middleware('can:rates:read')->group(function () {
        Route::get('/zone-mappings', [ZoneMappingController::class, 'index'])->name('zone-mappings.index');
        Route::get('/zone-mappings/export-domestic', [ZoneMappingController::class, 'exportDomestic'])->name('zone-mappings.export-domestic');
        Route::get('/zone-mappings/export-international', [ZoneMappingController::class, 'exportInternational'])->name('zone-mappings.export-international');
    });
    Route::middleware('can:rates:update')->group(function () {
        Route::post('/zone-mappings/generate-domestic', [ZoneMappingController::class, 'generateDomestic'])->name('zone-mappings.generate-domestic');
        Route::post('/zone-mappings/apply-domestic-rule', [ZoneMappingController::class, 'applyDomesticRule'])->name('zone-mappings.apply-domestic-rule');
        Route::post('/zone-mappings/apply-international-rule', [ZoneMappingController::class, 'applyInternationalRule'])->name('zone-mappings.apply-international-rule');
        Route::post('/zone-mappings/generate-international', [ZoneMappingController::class, 'generateInternational'])->name('zone-mappings.generate-international');
        Route::patch('/zone-mappings/{zoneMapping}/zone', [ZoneMappingController::class, 'updateZone'])->name('zone-mappings.update-zone');
        Route::patch('/zone-country-mappings/{zoneCountryMapping}/zone', [ZoneMappingController::class, 'updateCountryZone'])->name('zone-mappings.update-country-zone');
        Route::post('/third-party-country-mappings', [ZoneMappingController::class, 'storeThirdParty'])->name('zone-mappings.third-party.store');
        Route::post('/third-party-country-mappings/generate', [ZoneMappingController::class, 'generateThirdParty'])->name('zone-mappings.third-party.generate');
        Route::post('/third-party-country-mappings/apply-rule', [ZoneMappingController::class, 'applyThirdPartyRule'])->name('zone-mappings.third-party.apply-rule');
        Route::patch('/third-party-country-mappings/{thirdPartyCountryMapping}/zone', [ZoneMappingController::class, 'updateThirdPartyZone'])->name('zone-mappings.third-party.update-zone');
        Route::delete('/third-party-country-mappings/{thirdPartyCountryMapping}', [ZoneMappingController::class, 'destroyThirdParty'])->name('zone-mappings.third-party.destroy');
        Route::post('/zone-mappings/import-domestic', [ZoneMappingController::class, 'importDomestic'])->name('zone-mappings.import-domestic');
        Route::post('/zone-mappings/import-international', [ZoneMappingController::class, 'importInternational'])->name('zone-mappings.import-international');
    });

    Route::middleware('can:billing:read')->group(function () {
        Route::get('/service-types', [ServiceTypeController::class, 'index'])->name('service-types.index');
        Route::get('/standard-billing', [StandardBillingController::class, 'index'])->name('standard-billing.index');
        Route::get('/standard-billing/export', [StandardBillingController::class, 'exportAll'])->name('standard-billing.export');
        Route::get('/standard-billing/{tariff}/zone-prices/export', [StandardBillingController::class, 'exportZonePrices'])->name('standard-billing.zone-prices.export');
        Route::get('/origin-destination-billing/export', [OriginDestinationTariffController::class, 'export'])->name('origin-destination-billing.export');
        Route::get('/vehicle-types', [VehicleTypeController::class, 'index'])->name('vehicle-types.index');
        Route::get('/fleet-billing/export', [FleetBillingTariffController::class, 'export'])->name('fleet-billing.export');
        Route::get('/rate-checker', [RateCheckerController::class, 'index'])->name('rate-checker.index');
        Route::post('/rate-checker/quote', [QuoteController::class, 'store'])->name('quotes.store');
        Route::get('/additional-services', [AdditionalServiceController::class, 'index'])->name('additional-services.index');
        Route::get('/onforwarding-classifications', [OnforwardingClassificationController::class, 'index'])->name('onforwarding-classifications.index');
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    });
    Route::middleware('can:billing:update')->group(function () {
        Route::get('/service-types/create', [ServiceTypeController::class, 'create'])->name('service-types.create');
        Route::post('/service-types', [ServiceTypeController::class, 'store'])->name('service-types.store');
        Route::get('/service-types/{serviceType}/edit', [ServiceTypeController::class, 'edit'])->name('service-types.edit');
        Route::put('/service-types/{serviceType}', [ServiceTypeController::class, 'update'])->name('service-types.update');
        Route::delete('/service-types/{serviceType}', [ServiceTypeController::class, 'destroy'])->name('service-types.destroy');
        Route::get('/additional-services/create', [AdditionalServiceController::class, 'create'])->name('additional-services.create');
        Route::post('/additional-services', [AdditionalServiceController::class, 'store'])->name('additional-services.store');
        Route::get('/additional-services/{additionalService}/edit', [AdditionalServiceController::class, 'edit'])->name('additional-services.edit');
        Route::put('/additional-services/{additionalService}', [AdditionalServiceController::class, 'update'])->name('additional-services.update');
        Route::delete('/additional-services/{additionalService}', [AdditionalServiceController::class, 'destroy'])->name('additional-services.destroy');
        Route::get('/standard-billing/create', [StandardBillingController::class, 'create'])->name('standard-billing.create');
        Route::post('/standard-billing', [StandardBillingController::class, 'store'])->name('standard-billing.store');
        Route::post('/standard-billing/import', [StandardBillingController::class, 'importAll'])->name('standard-billing.import');
        Route::get('/standard-billing/{tariff}/edit', [StandardBillingController::class, 'edit'])->name('standard-billing.edit');
        Route::put('/standard-billing/{tariff}', [StandardBillingController::class, 'update'])->name('standard-billing.update');
        Route::delete('/standard-billing/{tariff}', [StandardBillingController::class, 'destroy'])->name('standard-billing.destroy');
        Route::post('/standard-billing/{tariff}/zone-prices/import', [StandardBillingController::class, 'importZonePrices'])->name('standard-billing.zone-prices.import');
        Route::get('/origin-destination-billing/create', [OriginDestinationTariffController::class, 'create'])->name('origin-destination-billing.create');
        Route::post('/origin-destination-billing', [OriginDestinationTariffController::class, 'store'])->name('origin-destination-billing.store');
        Route::post('/origin-destination-billing/import', [OriginDestinationTariffController::class, 'import'])->name('origin-destination-billing.import');
        Route::get('/origin-destination-billing/{tariff}/edit', [OriginDestinationTariffController::class, 'edit'])->name('origin-destination-billing.edit');
        Route::put('/origin-destination-billing/{tariff}', [OriginDestinationTariffController::class, 'update'])->name('origin-destination-billing.update');
        Route::delete('/origin-destination-billing/{tariff}', [OriginDestinationTariffController::class, 'destroy'])->name('origin-destination-billing.destroy');
        Route::get('/vehicle-types/create', [VehicleTypeController::class, 'create'])->name('vehicle-types.create');
        Route::post('/vehicle-types', [VehicleTypeController::class, 'store'])->name('vehicle-types.store');
        Route::get('/vehicle-types/{vehicleType}/edit', [VehicleTypeController::class, 'edit'])->name('vehicle-types.edit');
        Route::put('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'update'])->name('vehicle-types.update');
        Route::delete('/vehicle-types/{vehicleType}', [VehicleTypeController::class, 'destroy'])->name('vehicle-types.destroy');
        Route::get('/fleet-billing/create', [FleetBillingTariffController::class, 'create'])->name('fleet-billing.create');
        Route::post('/fleet-billing', [FleetBillingTariffController::class, 'store'])->name('fleet-billing.store');
        Route::post('/fleet-billing/import', [FleetBillingTariffController::class, 'import'])->name('fleet-billing.import');
        Route::get('/fleet-billing/{tariff}/edit', [FleetBillingTariffController::class, 'edit'])->name('fleet-billing.edit');
        Route::put('/fleet-billing/{tariff}', [FleetBillingTariffController::class, 'update'])->name('fleet-billing.update');
        Route::delete('/fleet-billing/{tariff}', [FleetBillingTariffController::class, 'destroy'])->name('fleet-billing.destroy');
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

    // Client accounts (individual/organization) + per-client billing.
    Route::middleware('can:clients:read')->group(function () {
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    });
    Route::middleware('can:clients:create')->group(function () {
        Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    });
    // /clients/{user} registered after /clients/create above - same
    // 2-segment shape, so registration order decides which wins.
    Route::middleware('can:clients:read')->group(function () {
        Route::get('/clients/{user}', [ClientController::class, 'show'])->name('clients.show');
        Route::get('/clients/{user}/accounts/{account}', [ClientController::class, 'show'])->name('clients.accounts.show');
    });
    Route::middleware('can:clients:update')->group(function () {
        Route::get('/clients/{user}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{user}', [ClientController::class, 'update'])->name('clients.update');
        Route::post('/clients/{user}/upgrade', [ClientController::class, 'upgrade'])->name('clients.upgrade');

        Route::post('/clients/{user}/accounts', [ClientController::class, 'storeAccount'])->name('clients.accounts.store');
        Route::post('/clients/{user}/accounts/{account}/set-default', [ClientController::class, 'setDefaultAccount'])->name('clients.accounts.set-default');
        Route::put('/clients/{user}/accounts/{account}/billing-info', [ClientController::class, 'updateAccountBillingInfo'])->name('clients.accounts.billing-info.update');
        Route::put('/clients/{user}/accounts/{account}/profile', [ClientController::class, 'updateAccountProfile'])->name('clients.accounts.profile.update');
        Route::put('/clients/{user}/accounts/{account}/status', [ClientController::class, 'updateAccountStatus'])->name('clients.accounts.status.update');
        Route::delete('/clients/{user}/accounts/{account}', [ClientController::class, 'destroyAccount'])->name('clients.accounts.destroy');

        Route::post('/clients/{user}/accounts/{account}/discounts', [ClientController::class, 'storeDiscount'])->name('clients.discounts.store');
        Route::delete('/clients/{user}/discounts/{discount}', [ClientController::class, 'destroyDiscount'])->name('clients.discounts.destroy');
        Route::post('/clients/{user}/accounts/{account}/special-tariffs', [ClientController::class, 'storeSpecialTariff'])->name('clients.special-tariffs.store');
        Route::delete('/clients/{user}/special-tariffs/{tariff}', [ClientController::class, 'destroySpecialTariff'])->name('clients.special-tariffs.destroy');
        Route::put('/clients/{user}/special-tariffs/{tariff}', [ClientController::class, 'updateSpecialTariff'])->name('clients.special-tariffs.update');

        Route::post('/clients/{user}/accounts/{account}/od-tariffs', [ClientController::class, 'storeOriginDestinationTariff'])->name('clients.od-tariffs.store');
        Route::delete('/clients/{user}/od-tariffs/{tariff}', [ClientController::class, 'destroyOriginDestinationTariff'])->name('clients.od-tariffs.destroy');
        Route::put('/clients/{user}/od-tariffs/{tariff}', [ClientController::class, 'updateOriginDestinationTariff'])->name('clients.od-tariffs.update');

        Route::post('/clients/{user}/accounts/{account}/fleet-tariffs', [ClientController::class, 'storeFleetTariff'])->name('clients.fleet-tariffs.store');
        Route::delete('/clients/{user}/fleet-tariffs/{tariff}', [ClientController::class, 'destroyFleetTariff'])->name('clients.fleet-tariffs.destroy');
        Route::put('/clients/{user}/fleet-tariffs/{tariff}', [ClientController::class, 'updateFleetTariff'])->name('clients.fleet-tariffs.update');

        Route::post('/clients/{user}/accounts/{account}/special-tariffs/import', [ClientController::class, 'importSpecialTariff'])->name('clients.special-tariffs.import');
        Route::post('/clients/{user}/accounts/{account}/od-tariffs/import', [ClientController::class, 'importOriginDestinationTariff'])->name('clients.od-tariffs.import');
        Route::post('/clients/{user}/accounts/{account}/fleet-tariffs/import', [ClientController::class, 'importFleetTariff'])->name('clients.fleet-tariffs.import');
        Route::get('/clients/tariff-template/{type}', [ClientController::class, 'downloadTariffTemplate'])->name('clients.tariff-template');

        Route::put('/clients/{user}/accounts/{account}/billing-models', [ClientController::class, 'updateDisabledBillingModels'])->name('clients.billing-models.update');
        Route::put('/clients/{user}/accounts/{account}/billing-mode', [ClientController::class, 'updateBillingModelMode'])->name('clients.billing-mode.update');
        Route::put('/clients/{user}/accounts/{account}/billing-fallback', [ClientController::class, 'updateBillingModelFallback'])->name('clients.billing-fallback.update');

        Route::post('/clients/{user}/accounts/{account}/departments', [ClientController::class, 'storeDepartment'])->name('clients.departments.store');
        Route::delete('/clients/{user}/departments/{department}', [ClientController::class, 'destroyDepartment'])->name('clients.departments.destroy');

        Route::post('/clients/{user}/accounts/{account}/users', [ClientController::class, 'storeSubUser'])->name('clients.sub-users.store');
        Route::put('/clients/{user}/users/{subUser}', [ClientController::class, 'updateSubUser'])->name('clients.sub-users.update');
        Route::delete('/clients/{user}/users/{subUser}', [ClientController::class, 'destroySubUser'])->name('clients.sub-users.destroy');

        Route::post('/clients/{user}/accounts/{account}/services', [ClientController::class, 'storeServiceSubscription'])->name('clients.services.store');

        Route::post('/clients/{user}/documents', [ClientController::class, 'storeDocument'])->name('clients.documents.store');
        Route::delete('/clients/{user}/documents/{document}', [ClientController::class, 'destroyDocument'])->name('clients.documents.destroy');

        Route::post('/clients/{user}/accounts/{account}/api-access', [ClientController::class, 'generateApiAccess'])->name('clients.api-access.generate');
        Route::put('/clients/{user}/api-clients/{apiClient}', [ClientController::class, 'updateApiSettings'])->name('clients.api-access.update');
        Route::post('/clients/{user}/api-clients/{apiClient}/ip-whitelist', [ClientController::class, 'storeIpWhitelist'])->name('clients.ip-whitelist.store');
        Route::delete('/clients/{user}/ip-whitelist/{ipWhitelist}', [ClientController::class, 'destroyIpWhitelist'])->name('clients.ip-whitelist.destroy');
        Route::post('/clients/{user}/api-clients/{apiClient}/webhooks', [ClientController::class, 'storeWebhook'])->name('clients.webhooks.store');
        Route::delete('/clients/{user}/webhooks/{webhook}', [ClientController::class, 'destroyWebhook'])->name('clients.webhooks.destroy');

        Route::put('/clients/{user}/accounts/{account}/managerial', [ClientController::class, 'updateManagerial'])->name('clients.managerial.update');
    });
    Route::middleware('can:clients:delete')->group(function () {
        Route::delete('/clients/{user}', [ClientController::class, 'destroy'])->name('clients.destroy');
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

// Public — Paystack calls this directly from their own servers, no
// staff session involved. Trust comes from signature verification
// inside the controller, not from auth/staff middleware.
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');
