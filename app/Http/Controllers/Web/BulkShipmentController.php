<?php

namespace App\Http\Controllers\Web;

use App\Models\BulkShipmentBatch;
use App\Models\BulkShipmentBatchRow;
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
            'clientAccounts' => ClientAccount::orderBy('account_name')->get(['id', 'account_name', 'account_number', 'payment_type']),
            'serviceTypes' => ServiceType::whereIn('billing_model', self::ALLOWED_BILLING_MODELS)->orderBy('name')->get(['id', 'name', 'billing_model']),
            'billingModels' => $billingModels,
            'originLabel' => $originLabel,
            'originHubs' => $hubs,
            'originOutlets' => $outlets,
            // The booking hub/outlet isn't necessarily where a batch's
            // shipments actually get picked up from — any user can be
            // arranging a remote pickup while booking through their
            // own hub — so origin state/town is its own explicit
            // choice here, states each carrying their own cities for
            // the same cascading dropdown pattern the bulk template
            // already uses.
            'states' => \App\Models\State::with('cities')->orderBy('name')->get(),
            // Same gating the single-shipment form already uses —
            // cash needs an outlet that actually accepts it (or no
            // specific outlet at all), paystack needs the company-
            // wide setting on.
            'canCollectCash' => ! $user->outlet_id || (Outlet::find($user->outlet_id)?->can_collect_cash ?? true),
            'paystackEnabled' => Setting::current()->paystack_enabled,
        ]);
    }

    /**
     * Streams a freshly-generated .xlsx every time, never a cached
     * file — the State/City dropdowns are built from whatever's
     * currently in the database at the moment of download, so a city
     * added five minutes ago is already there.
     */
    /**
     * Prints every shipment actually created under this batch as its
     * own real label — not a summary document, the exact same label
     * a single shipment would print, one after another for the whole
     * batch. Reuses the six existing label templates completely
     * unchanged (rendering each shipment's label exactly as its own
     * label() route would, then combining the resulting pages into
     * one print job) rather than duplicating their markup, so a
     * batch's labels can never drift from what printing one shipment
     * normally produces.
     */
    /**
     * The detail page for one batch — everything about it in one
     * place rather than scattered across upload/review/print: sender
     * and service details, when it was generated, how many shipments
     * it actually produced, and every one of those shipments as its
     * own row (paginated, since a batch can run to hundreds).
     * Printing is offered as an action here rather than being the
     * click-through destination itself, so seeing what's in the
     * batch and printing it are two separate, deliberate steps.
     */
    public function show(BulkShipmentBatch $batch): View
    {
        $batch->load(['clientAccount', 'serviceType', 'originHub', 'originOutlet', 'originCity.state']);

        $shipments = $batch->shipments()
            ->with(['serviceType', 'originCity.state', 'destinationCity.state'])
            ->orderBy('created_at')
            ->paginate(25);

        return view('shipments.bulk.show', [
            'batch' => $batch,
            'shipments' => $shipments,
            'pendingCount' => $batch->rows()->count(),
        ]);
    }

    public function print(Request $request, BulkShipmentBatch $batch): Response
    {
        $batch->load(['shipments' => fn ($q) => $q->with(['serviceType', 'originCity', 'destinationCity', 'originHub', 'destinationHub', 'clientAccount'])]);

        $settings = Setting::current();
        $design = in_array($settings->label_design, ['classic', 'modern', 'compact'], true) ? $settings->label_design : 'classic';
        $printSize = in_array($request->query('size'), ['4x6', '2x1'], true) ? $request->query('size') : $settings->waybill_thermal_size;

        if ($batch->shipments->isEmpty()) {
            abort(404, 'No shipments have been created under this batch yet.');
        }

        // A shipment awaiting Paystack payment doesn't get a printed
        // label until it's confirmed paid — same rule as printing a
        // single shipment, just applied per-row here since a batch's
        // shipments can end up with different payment statuses even
        // though they share one payment_method (each is its own
        // Paystack transaction).
        $printableShipments = $batch->shipments->reject->isPaymentPending();
        $pendingCount = $batch->shipments->count() - $printableShipments->count();

        if ($printableShipments->isEmpty()) {
            abort(403, 'Every shipment in this batch is still awaiting Paystack payment — nothing to print yet.');
        }

        $styleBlock = null;
        $scriptBlock = null;
        $pageBlocks = [];

        foreach ($printableShipments as $shipment) {
            $totalPieces = max((int) ($shipment->quantity ?? 1), 1);
            $pieces = [];
            for ($i = 1; $i <= $totalPieces; $i++) {
                $pieceCode = $totalPieces > 1 ? "{$shipment->tracking_number}-{$i}/{$totalPieces}" : $shipment->tracking_number;
                $codeSvg = null;
                if ($settings->waybill_show_qr) {
                    $codeSvg = $settings->label_barcode_type === 'barcode'
                        ? (new \Picqer\Barcode\BarcodeGeneratorSVG())->getBarcode($pieceCode, \Picqer\Barcode\BarcodeGeneratorSVG::TYPE_CODE_128)
                        : \SimpleSoftwareIO\QrCode\Facades\QrCode::size(160)->generate($pieceCode);
                }
                $pieces[] = ['number' => $i, 'total' => $totalPieces, 'code' => $pieceCode, 'codeSvg' => $codeSvg];
            }

            $clientLogoUrl = $shipment->clientAccount?->logo_url;
            $html = view("shipments.label.{$design}-{$printSize}", compact('shipment', 'settings', 'clientLogoUrl', 'printSize', 'pieces'))->render();

            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new \DOMXPath($dom);

            // The auto-fit script's own max-height and shrink-floor
            // values differ by size (4×6 vs 2×1) and slightly by
            // design (compact's 4×6 is a touch taller) — pulling the
            // real <script> block straight out of the actual
            // rendered template, the same way the <style> block is,
            // keeps this exactly in sync rather than risking a
            // hardcoded value silently wrong for whichever size
            // wasn't being tested.
            if ($styleBlock === null) {
                $styleNode = $xpath->query('//style')->item(0);
                $styleBlock = $styleNode ? $dom->saveHTML($styleNode) : '';

                $scriptNode = $xpath->query('//script')->item(0);
                $scriptBlock = $scriptNode ? $dom->saveHTML($scriptNode) : '';
            }

            foreach ($xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' page ')]") as $pageNode) {
                $pageBlocks[] = $dom->saveHTML($pageNode);
            }
        }

        $pendingNote = $pendingCount > 0 ? " <span style=\"margin-left:8px;color:#b45309;font-size:12px;\">{$pendingCount} shipment(s) skipped — still awaiting payment</span>" : '';

        $combined = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Labels — ' . $batch->batch_number . '</title>'
            . $styleBlock
            . '<style>.toolbar{display:flex;align-items:center;gap:12px;padding:10px;background:#f2f2f2;border-bottom:2px solid #ccc;}.print-btn{padding:8px 16px;font-size:13px;border:1px solid #111;background:#111;color:#fff;border-radius:4px;cursor:pointer;}@media print{.toolbar{display:none!important;}}</style>'
            . '</head><body>'
            . '<div class="toolbar"><button class="print-btn" onclick="window.print()">Print all ' . count($pageBlocks) . ' label(s)</button>' . $pendingNote . '</div>'
            . implode('', $pageBlocks)
            . $scriptBlock
            . '</body></html>';

        return response($combined);
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
            'origin_city_id' => 'required|exists:cities,id',
            'origin_hub_id' => 'nullable|exists:hubs,id',
            'origin_outlet_id' => 'nullable|exists:outlets,id',
            'payment_method' => 'nullable|string|in:cash,paystack,deferred',
        ]);

        [$originHubId, $originOutletId, $originError] = $this->resolveOrigin($user, $data['origin_hub_id'] ?? null, $data['origin_outlet_id'] ?? null);

        if ($originError) {
            return redirect()->route('shipments.bulk.create')->withErrors(['sender_address' => $originError])->withInput();
        }

        $account = ! empty($data['client_account_id']) ? ClientAccount::find($data['client_account_id']) : null;

        // A credit account defaults to deferred but can still choose
        // to pay this batch now — cash/paystack is respected either
        // way. Everyone else (walk-in included) has no invoice to
        // fall back on, so a real choice is required, not optional.
        if (! $account?->isCreditAccount() && ! in_array($data['payment_method'] ?? null, ['cash', 'paystack'], true)) {
            return redirect()->route('shipments.bulk.create')->withErrors(['sender_address' => 'A payment method (cash or online) is required — this account has no credit facility to defer payment to.'])->withInput();
        }

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
            'origin_city_id' => $data['origin_city_id'],
            'origin_hub_id' => $originHubId,
            'origin_outlet_id' => $originOutletId,
            'payment_method' => $data['payment_method'] ?? null,
            'created_by_user_id' => $user->id,
        ]);

        return redirect()->route('shipments.bulk.upload', $batch);
    }

    /**
     * Step 2: the upload page for one specific, already-created
     * batch. Once the batch has any real shipments, uploading is
     * over — sent straight to the print view instead, since printing
     * is the only thing left to do with a batch at that point.
     */
    public function showUpload(BulkShipmentBatch $batch): View|RedirectResponse
    {
        if ($batch->hasCreatedShipments()) {
            return redirect()->route('shipments.bulk.print', $batch);
        }

        return view('shipments.bulk.upload', ['batch' => $batch, 'pendingCount' => $batch->rows()->count()]);
    }

    /**
     * Parses and validates the upload against this batch's own
     * context, creating nothing yet — the rows are persisted onto
     * the batch (accumulating with anything already pending from an
     * earlier upload, not replacing it) and the person is sent to
     * review() to see everything currently pending, valid and
     * invalid together, before anything is actually created.
     */
    public function preview(Request $request, BulkShipmentBatch $batch): RedirectResponse
    {
        if ($batch->hasCreatedShipments()) {
            return redirect()->route('shipments.bulk.print', $batch);
        }

        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);

        $path = $request->file('file')->getRealPath();
        $rows = $this->import->parseFile($path);

        if (count($rows) === 0) {
            return redirect()->route('shipments.bulk.upload', $batch)->withErrors(['file' => 'No shipment rows found in this file.']);
        }

        if (count($rows) > BulkShipmentTemplateService::ROW_LIMIT) {
            return redirect()->route('shipments.bulk.upload', $batch)->withErrors(['file' => 'This file has ' . count($rows) . ' rows — the limit is ' . BulkShipmentTemplateService::ROW_LIMIT . ' per upload.']);
        }

        $batchContext = [
            'client_account_id' => $batch->client_account_id,
            'client_user_id' => $batch->client_user_id,
            'bulk_shipment_batch_id' => $batch->id,
            'service_type_id' => $batch->service_type_id,
            'origin_hub_id' => $batch->origin_hub_id,
            'origin_outlet_id' => $batch->origin_outlet_id,
            'origin_address' => $batch->sender_address,
            // The batch's own explicit origin city — not the booking
            // hub's — since the hub is just where this was booked
            // from, not necessarily where the shipments are actually
            // being picked up.
            'origin_city_id' => $batch->origin_city_id,
            'sender_name' => $batch->sender_name,
            'sender_phone' => $batch->sender_phone,
            // Ignored entirely for a credit account regardless (see
            // ShipmentCreationService::resolveCollectionMethod), but
            // for a walk-in/non-credit account this is what actually
            // marks every shipment the batch produces as paid now.
            'payment_method' => $batch->payment_method,
        ];

        $result = $this->import->validateRows($rows, $batchContext);
        $this->import->persistRows($batch, $result);

        return redirect()->route('shipments.bulk.review', $batch);
    }

    /**
     * Every row currently pending on this batch — across however
     * many uploads produced them — valid and invalid shown
     * separately. Nothing here has been created yet; this is purely
     * review, with a delete available per row (fixing one bad row
     * without needing to fix and re-upload the whole file) and, so
     * long as the batch has no shipments yet, a way back to upload
     * more.
     */
    public function review(BulkShipmentBatch $batch): View
    {
        $rows = $batch->rows()->orderBy('source_row_number')->get();

        return view('shipments.bulk.review', [
            'batch' => $batch,
            'validRows' => $rows->where('status', 'valid'),
            'invalidRows' => $rows->where('status', 'invalid'),
        ]);
    }

    /**
     * Removes one pending row — the "delete in case of errors" path,
     * for a single bad row rather than needing to fix and re-upload
     * the whole file for one mistake. Left available even once the
     * batch has shipments, since a row that failed at create time
     * (a genuinely unpriceable route, say) still needs to be
     * cleaned up or corrected — only new file uploads are cut off
     * once a batch has real shipments, not managing what's already
     * pending.
     */
    public function destroyRow(BulkShipmentBatch $batch, BulkShipmentBatchRow $row): RedirectResponse
    {
        abort_unless($row->bulk_shipment_batch_id === $batch->id, 404);

        $row->delete();

        return redirect()->route('shipments.bulk.review', $batch)->with('status', 'Row removed.');
    }

    /**
     * Creates shipments from every row currently sitting as 'valid'
     * on this batch — not from a token, not from whatever the most
     * recent upload happened to contain, but from the batch's actual
     * current pending state, however many uploads and deletions
     * built up to it. A row that succeeds is removed as it goes; one
     * that fails (a genuinely unpriceable route, say) is left in
     * place for review rather than silently lost. Left available
     * even once the batch already has shipments, since this is what
     * lets a leftover failed row be retried after being fixed —
     * only new uploads are cut off at that point.
     */
    public function store(BulkShipmentBatch $batch): View|RedirectResponse
    {
        $validRows = $batch->rows()->where('status', 'valid')->orderBy('source_row_number')->get();

        // A hard abort() here shows Laravel's raw exception page for
        // what's actually a routine, recoverable situation - most
        // commonly the form being submitted twice (a double-click, or
        // the browser resubmitting after a back navigation), where
        // the first submission already consumed every valid row.
        // Sent back to the batch itself with a plain explanation
        // instead, since there's nothing wrong to fix, just nothing
        // left pending right now.
        if ($validRows->isEmpty()) {
            return redirect()->route('shipments.bulk.show', $batch)->with('status', 'No pending rows to create — they may already have been processed, or none have been uploaded yet.');
        }

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
