<?php

namespace App\Http\Controllers\Web;

use App\Models\BulkShipmentBatch;
use App\Models\ClientAccount;
use App\Models\Hub;
use App\Models\Outlet;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Services\BulkShipmentImportService;
use App\Services\BulkShipmentTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Bulk shipment creation from a CSV/Excel upload, split into two
 * real steps rather than one combined submission. Step 1 (shipper +
 * service type details) creates a persisted batch with its own
 * batch number; step 2 is the file upload against that batch, and
 * can be repeated as many times as needed — a CSV with errors gets
 * fixed and re-uploaded to the SAME batch, not re-entered from
 * scratch. Origin isn't a form field at all: it's resolved from
 * whichever hub/outlet the person creating the batch is themselves
 * assigned to, the same way scanning locations already work — an
 * outlet books a walk-in bulk batch under its own account, not by
 * picking itself from a list. Only the two billing models that
 * actually fit a bulk approach (Zoning and Weight, Origin to
 * Destination) are offered; Fleet Billing doesn't apply per-shipment
 * the way bulk upload needs.
 */
class BulkShipmentController extends \App\Http\Controllers\Controller
{
    public function __construct(
        private BulkShipmentTemplateService $template,
        private BulkShipmentImportService $import,
    ) {
    }

    /**
     * The billing models this feature supports — Fleet Billing is
     * deliberately excluded, since it bills a dedicated vehicle/
     * contract, not a per-shipment rate the way bulk upload needs.
     */
    private const ALLOWED_BILLING_MODELS = ['standard_billing', 'origin_destination_billing'];

    /**
     * Every batch ever created, newest first — the record of who
     * uploaded what, when, and how many shipments actually came out
     * of it, with a way straight into starting a new one.
     */
    public function index(): View
    {
        $batches = BulkShipmentBatch::with(['clientAccount', 'serviceType', 'createdBy'])
            ->withCount('shipments')
            ->latest()
            ->paginate(20);

        return view('shipments.bulk.index', compact('batches'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $billingModels = collect(Setting::BILLING_MODELS)
            ->only(self::ALLOWED_BILLING_MODELS)
            ->intersectByKeys(array_flip(array_keys(Setting::current()->supportedBillingModels())));

        [$hubs, $outlets, $originLabel] = $this->resolveOriginOptions($user);

        return view('shipments.bulk.create', [
            'clientAccounts' => ClientAccount::orderBy('account_name')->get(['id', 'account_name', 'account_number']),
            'serviceTypes' => ServiceType::whereIn('billing_model', self::ALLOWED_BILLING_MODELS)->orderBy('name')->get(['id', 'name', 'billing_model']),
            'billingModels' => $billingModels,
            'originLabel' => $originLabel,
            'originHubs' => $hubs,
            'originOutlets' => $outlets,
        ]);
    }

    /**
     * Streams a freshly-generated .xlsx every time, never a cached
     * file — the State/City dropdowns are built from whatever's
     * currently in the database at the moment of download, so a city
     * added five minutes ago is already there.
     */
    /**
     * The printable record of one batch — shipper details at the
     * top, then every shipment actually created under it, same
     * tabular shape as the manifest/trip documents. Looked up by the
     * batch's own BULK- number the same way TRF-/DEL-/MAN-/TRIP-
     * numbers already work from the general Print Documents page.
     */
    public function print(BulkShipmentBatch $batch): View
    {
        $batch->load(['clientAccount', 'serviceType', 'originHub', 'originOutlet', 'shipments' => fn ($q) => $q->with(['serviceType', 'destinationCity.state'])]);

        $settings = Setting::current();

        return view('shipments.bulk.print', compact('batch', 'settings'));
    }

    public function downloadTemplate(): Response
    {
        $spreadsheet = $this->template->generate();

        $tempPath = tempnam(sys_get_temp_dir(), 'bulk-shipment-template') . '.xlsx';
        $this->template->save($spreadsheet, $tempPath);

        $contents = file_get_contents($tempPath);
        @unlink($tempPath);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="bulk-shipment-template.xlsx"',
        ]);
    }

    /**
     * Step 1: creates the batch record and its number, then sends
     * the person straight to step 2 (the upload page for this
     * specific batch) — the whole point being that batch now exists
     * independently of any one upload attempt.
     */
    public function storeBatch(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'client_account_id' => 'nullable|exists:client_accounts,id',
            'billing_model' => 'required|string|in:' . implode(',', self::ALLOWED_BILLING_MODELS),
            'service_type_id' => 'required|exists:service_types,id',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string|max:150',
            'sender_email' => 'nullable|email|max:255',
            'origin_hub_id' => 'nullable|exists:hubs,id',
            'origin_outlet_id' => 'nullable|exists:outlets,id',
        ]);

        [$originHubId, $originOutletId, $originError] = $this->resolveOrigin($user, $data['origin_hub_id'] ?? null, $data['origin_outlet_id'] ?? null);

        if ($originError) {
            return redirect()->route('shipments.bulk.create')->withErrors(['sender_address' => $originError])->withInput();
        }

        $account = ! empty($data['client_account_id']) ? ClientAccount::find($data['client_account_id']) : null;

        $batch = BulkShipmentBatch::create([
            'batch_number' => BulkShipmentBatch::generateBatchNumber(),
            'client_account_id' => $account?->id,
            'client_user_id' => $account?->client_user_id,
            'billing_model' => $data['billing_model'],
            'service_type_id' => $data['service_type_id'],
            'sender_name' => $data['sender_name'],
            'sender_phone' => $data['sender_phone'],
            'sender_address' => $data['sender_address'],
            'sender_email' => $data['sender_email'] ?? null,
            'origin_hub_id' => $originHubId,
            'origin_outlet_id' => $originOutletId,
            'created_by_user_id' => $user->id,
        ]);

        return redirect()->route('shipments.bulk.upload', $batch);
    }

    /**
     * Step 2: the upload page for one specific, already-created
     * batch. Visiting this again (after a failed/partial previous
     * attempt) is the normal way to correct and re-submit a CSV.
     */
    public function showUpload(BulkShipmentBatch $batch): View
    {
        return view('shipments.bulk.upload', ['batch' => $batch]);
    }

    /**
     * Parses and validates the upload against this batch's own
     * context, creating nothing yet. Valid and invalid rows are
     * shown separately — never merged, same as every other batch
     * operation in this app — and the validated, fully-resolved data
     * for the valid rows is stashed server-side under a one-time
     * token (too much data for 1000 rows to round-trip through
     * hidden form fields) for store() to pick up once confirmed.
     */
    public function preview(Request $request, BulkShipmentBatch $batch): View|RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);

        $path = $request->file('file')->getRealPath();
        $rows = $this->import->parseFile($path);

        if (count($rows) === 0) {
            return redirect()->route('shipments.bulk.upload', $batch)->withErrors(['file' => 'No shipment rows found in this file.']);
        }

        if (count($rows) > BulkShipmentTemplateService::ROW_LIMIT) {
            return redirect()->route('shipments.bulk.upload', $batch)->withErrors(['file' => 'This file has ' . count($rows) . ' rows — the limit is ' . BulkShipmentTemplateService::ROW_LIMIT . ' per upload.']);
        }

        $originHub = $batch->origin_hub_id ? Hub::find($batch->origin_hub_id) : null;

        $batchContext = [
            'client_account_id' => $batch->client_account_id,
            'client_user_id' => $batch->client_user_id,
            'bulk_shipment_batch_id' => $batch->id,
            'service_type_id' => $batch->service_type_id,
            'origin_hub_id' => $batch->origin_hub_id,
            'origin_outlet_id' => $batch->origin_outlet_id,
            'origin_address' => $batch->sender_address,
            'origin_city_id' => $originHub?->city_id,
            'sender_name' => $batch->sender_name,
            'sender_phone' => $batch->sender_phone,
        ];

        $result = $this->import->validateRows($rows, $batchContext);

        $token = \Illuminate\Support\Str::random(32);
        \Illuminate\Support\Facades\Storage::disk('local')->put("bulk-imports/{$token}.json", json_encode($result['valid']));

        return view('shipments.bulk.preview', [
            'batch' => $batch,
            'token' => $token,
            'validRows' => $result['valid'],
            'invalidRows' => $result['invalid'],
        ]);
    }

    /**
     * Only the rows preview() already validated are ever created
     * here — this endpoint never re-parses or re-validates the
     * upload itself, it only reads back what was already checked.
     */
    public function store(Request $request, BulkShipmentBatch $batch): View
    {
        $request->validate(['token' => 'required|string']);

        $path = "bulk-imports/{$request->input('token')}.json";
        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        abort_unless($disk->exists($path), 404, 'This preview has expired — please upload the file again.');

        $validRows = json_decode($disk->get($path), true);
        $disk->delete($path);

        $result = $this->import->createShipments($validRows);

        return view('shipments.bulk.result', [
            'batch' => $batch,
            'created' => $result['created'],
            'failed' => $result['failed'],
        ]);
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: ?string} [hubId, outletId, errorMessage]
     */
    private function resolveOrigin($user, ?int $requestedHubId, ?int $requestedOutletId): array
    {
        if ($user->hasOutletAccess()) {
            $outlet = Outlet::find($user->outlet_id);

            return [$outlet?->hub_id, $user->outlet_id, null];
        }

        if ($user->hasHubAccess()) {
            return [$user->hub_id, null, null];
        }

        // Global/regional staff aren't pinned to one location, so
        // they're the only ones who get an actual choice — the same
        // "free to pick, but only within their own region" rule
        // scanning locations already follow, not something new to
        // bulk upload specifically.
        if ($user->hasRegionAccess()) {
            if ($requestedOutletId) {
                $outlet = Outlet::find($requestedOutletId);
                if (! $outlet || $outlet->hub?->region_id !== $user->region_id) {
                    return [null, null, "That outlet isn't in your region."];
                }

                return [$outlet->hub_id, $requestedOutletId, null];
            }

            if ($requestedHubId) {
                $hub = Hub::find($requestedHubId);
                if (! $hub || $hub->region_id !== $user->region_id) {
                    return [null, null, "That hub isn't in your region."];
                }

                return [$requestedHubId, null, null];
            }

            return [null, null, 'Pick an origin hub or outlet for this batch.'];
        }

        if ($user->hasGlobalAccess()) {
            if (! $requestedHubId && ! $requestedOutletId) {
                return [null, null, 'Pick an origin hub or outlet for this batch.'];
            }

            if ($requestedOutletId) {
                $outlet = Outlet::find($requestedOutletId);

                return [$outlet?->hub_id, $requestedOutletId, null];
            }

            return [$requestedHubId, null, null];
        }

        return [null, null, "Bulk upload needs a single origin — your account isn't assigned to a specific hub or outlet, so there's nothing to book this batch from."];
    }

    /**
     * Global staff pick freely from every hub/outlet. Regional staff
     * pick freely, but only within their own region. Hub- and
     * outlet-scoped staff aren't offered a choice at all — their own
     * single location is shown as a fixed label instead, same as
     * everywhere else this pattern is used across the app.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: ?string} [hubs, outlets, lockedLabel]
     */
    private function resolveOriginOptions($user): array
    {
        if ($user->hasGlobalAccess()) {
            return [Hub::orderBy('name')->get(['id', 'name']), Outlet::orderBy('name')->get(['id', 'name', 'hub_id']), null];
        }

        if ($user->hasRegionAccess()) {
            $hubs = Hub::where('region_id', $user->region_id)->orderBy('name')->get(['id', 'name']);
            $outlets = Outlet::whereIn('hub_id', $hubs->pluck('id'))->orderBy('name')->get(['id', 'name', 'hub_id']);

            return [$hubs, $outlets, null];
        }

        if ($user->hasOutletAccess()) {
            return [collect(), collect(), Outlet::find($user->outlet_id)?->name];
        }

        if ($user->hasHubAccess()) {
            return [collect(), collect(), Hub::find($user->hub_id)?->name];
        }

        return [collect(), collect(), null];
    }
}
