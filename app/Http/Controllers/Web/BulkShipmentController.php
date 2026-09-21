<?php

namespace App\Http\Controllers\Web;

use App\Models\ClientAccount;
use App\Models\Hub;
use App\Models\Outlet;
use App\Models\ServiceType;
use App\Services\BulkShipmentImportService;
use App\Services\BulkShipmentTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Bulk shipment creation from a CSV/Excel upload. Batch-level
 * settings — client account, origin outlet, service type — are set
 * once for the whole upload, not per row; everything shipment-
 * specific (receiver, destination, weight, description) comes from
 * the file itself, one row per shipment. Follows the same
 * verify-then-confirm shape as every other batch operation in this
 * app: nothing is created until the previewed rows are explicitly
 * confirmed, and results are always reported success/error
 * separately, never merged.
 */
class BulkShipmentController extends \App\Http\Controllers\Controller
{
    public function __construct(
        private BulkShipmentTemplateService $template,
        private BulkShipmentImportService $import,
    ) {
    }

    public function create(): View
    {
        return view('shipments.bulk.create', [
            'clientAccounts' => ClientAccount::orderBy('account_name')->get(['id', 'account_name', 'account_number']),
            'outlets' => Outlet::orderBy('name')->get(['id', 'name', 'hub_id']),
            'hubs' => Hub::orderBy('name')->get(['id', 'name']),
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
            'rowLimit' => BulkShipmentTemplateService::ROW_LIMIT,
        ]);
    }

    /**
     * Streams a freshly-generated .xlsx every time, never a cached
     * file — the State/City dropdowns are built from whatever's
     * currently in the database at the moment of download, so a city
     * added five minutes ago is already there.
     */
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
     * Parses and validates the upload, creating nothing yet. Valid
     * and invalid rows are shown separately — never merged, same as
     * every other batch operation in this app — and the validated,
     * fully-resolved data for the valid rows is stashed server-side
     * under a one-time token (too much data for 1000 rows to
     * round-trip through hidden form fields) for store() to pick up
     * once the person actually confirms.
     */
    public function preview(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'client_account_id' => 'required|exists:client_accounts,id',
            'origin_hub_id' => 'nullable|exists:hubs,id',
            'origin_outlet_id' => 'nullable|exists:outlets,id',
            'service_type_id' => 'required|exists:service_types,id',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        if (empty($data['origin_hub_id']) && empty($data['origin_outlet_id'])) {
            return redirect()->route('shipments.bulk.create')->withErrors(['origin_hub_id' => 'Pick an origin hub or outlet for this batch.'])->withInput();
        }

        $account = ClientAccount::find($data['client_account_id']);

        $path = $request->file('file')->getRealPath();
        $rows = $this->import->parseFile($path);

        if (count($rows) === 0) {
            return redirect()->route('shipments.bulk.create')->withErrors(['file' => 'No shipment rows found in this file.'])->withInput();
        }

        if (count($rows) > BulkShipmentTemplateService::ROW_LIMIT) {
            return redirect()->route('shipments.bulk.create')->withErrors(['file' => 'This file has ' . count($rows) . ' rows — the limit is ' . BulkShipmentTemplateService::ROW_LIMIT . ' per upload.'])->withInput();
        }

        $batchContext = [
            'client_account_id' => $account->id,
            'client_user_id' => $account->client_user_id,
            'service_type_id' => $data['service_type_id'],
            'origin_hub_id' => $data['origin_hub_id'] ?? null,
            'origin_outlet_id' => $data['origin_outlet_id'] ?? null,
            'sender_name' => $data['sender_name'],
            'sender_phone' => $data['sender_phone'],
        ];

        $result = $this->import->validateRows($rows, $batchContext);

        $token = \Illuminate\Support\Str::random(32);
        \Illuminate\Support\Facades\Storage::disk('local')->put("bulk-imports/{$token}.json", json_encode($result['valid']));

        return view('shipments.bulk.preview', [
            'token' => $token,
            'validRows' => $result['valid'],
            'invalidRows' => $result['invalid'],
            'account' => $account,
        ]);
    }

    /**
     * Only the rows preview() already validated are ever created
     * here — this endpoint never re-parses or re-validates the
     * upload itself, it only reads back what was already checked.
     */
    public function store(Request $request): View
    {
        $request->validate(['token' => 'required|string']);

        $path = "bulk-imports/{$request->input('token')}.json";
        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        abort_unless($disk->exists($path), 404, 'This batch has expired — please upload the file again.');

        $validRows = json_decode($disk->get($path), true);
        $disk->delete($path);

        $result = $this->import->createShipments($validRows);

        return view('shipments.bulk.result', [
            'created' => $result['created'],
            'failed' => $result['failed'],
        ]);
    }
}
