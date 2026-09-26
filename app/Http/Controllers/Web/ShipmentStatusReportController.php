<?php

namespace App\Http\Controllers\Web;

use App\Models\Outlet;
use App\Services\ShipmentStatusReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * "General report that oversees all shipment status" — every
 * shipment, filterable, access-level scoped the same way every other
 * report in this app already is, exportable to Excel via a streamed
 * cursor rather than one large in-memory result set.
 */
class ShipmentStatusReportController extends \App\Http\Controllers\Controller
{
    public function __construct(private ShipmentStatusReportService $report)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $shipments = $this->report->query($filters)->paginate(50)->withQueryString();

        return view('shipment-status-report.index', [
            'shipments' => $shipments,
            'report' => $this->report,
            'outlets' => auth()->user()->hasGlobalAccess() || auth()->user()->hasRegionAccess()
                ? Outlet::orderBy('name')->get(['id', 'name'])
                : collect(),
            'filters' => $request->only(['date_from', 'date_to', 'status', 'outlet_id']),
        ]);
    }

    /**
     * cursor() rather than get() — the columns requested cover the
     * full shipment lifecycle, exactly the kind of report someone
     * exports for a whole month or quarter at once, potentially tens
     * of thousands of rows. Streaming keeps memory flat regardless.
     */
    public function export(Request $request): Response
    {
        $filters = $this->resolveFilters($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Shipment Status');

        $headers = [
            'Waybill No', 'Origin Code', 'Origin State', 'Origin Town',
            'Destination Code', 'Destination State', 'Destination Town',
            'Receiver Name', 'Receiver Phone', 'Actual Recipient',
            'Chargeable Weight', 'Pieces', 'Item Description', 'Reference Number',
            'Amount Due', 'Amount Paid', 'Date Created', 'Pickup Date', 'Pickup Status',
            'Last Scan', 'Delivery Status', 'POD Posted By Name', 'Delivery Type',
            'Product Type', 'Expected Delivery Date', 'Delivery Date', 'Created By', 'Department Code',
        ];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        $row = 2;
        foreach ($this->report->query($filters)->cursor() as $shipment) {
            $columns = $this->rowFor($shipment);
            foreach ($columns as $i => $value) {
                $sheet->setCellValueByColumnAndRow($i + 1, $row, $value);
            }
            $row++;
        }

        foreach (range('A', 'AB') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'shipment-status') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download($path, 'shipment-status-' . now()->format('Ymd-His') . '.xlsx')->deleteFileAfterSend();
    }

    /**
     * Shared between the web table and the Excel export so the two
     * never drift apart on what each column actually means.
     */
    public function rowFor(\App\Models\Shipment $shipment): array
    {
        return [
            $shipment->tracking_number,
            $shipment->originHub?->code,
            $shipment->originCity?->state?->name,
            $shipment->originCity?->name,
            $shipment->destinationHub?->code,
            $shipment->destinationCity?->state?->name,
            $shipment->destinationCity?->name,
            $shipment->receiver_name,
            $shipment->receiver_phone,
            $shipment->actual_recipient,
            $shipment->chargeable_weight_kg,
            $shipment->quantity,
            $shipment->package_description,
            $shipment->payment_reference,
            $shipment->total_amount,
            $shipment->hasCollectedPayment() ? $shipment->total_amount : 0,
            optional($shipment->created_at)->format('Y-m-d H:i'),
            $shipment->pickup_date ? \Illuminate\Support\Carbon::parse($shipment->pickup_date)->format('Y-m-d H:i') : null,
            $shipment->pickup_date ? 'Picked Up' : 'Not Picked Up',
            $shipment->last_scan_status,
            $shipment->current_status,
            $shipment->pod_handler_name,
            $shipment->shipping_type,
            $shipment->serviceType?->name,
            optional($shipment->promised_delivery_at)->format('Y-m-d'),
            optional($shipment->delivered_at)->format('Y-m-d H:i'),
            $shipment->createdBy?->name,
            $this->report->departmentCode($shipment),
        ];
    }

    private function resolveFilters(Request $request): array
    {
        $user = auth()->user();

        $filters = $request->only(['date_from', 'date_to', 'status']);

        if ($user->hasOutletAccess()) {
            $filters['outlet_ids'] = [$user->outlet_id];
        } elseif (! $user->hasGlobalAccess() && ! $user->hasRegionAccess()) {
            $filters['outlet_ids'] = Outlet::whereIn('hub_id', $user->accessibleHubIds())->pluck('id')->all();
        } elseif ($request->filled('outlet_id')) {
            $filters['outlet_ids'] = [$request->input('outlet_id')];
        }

        return $filters;
    }
}
