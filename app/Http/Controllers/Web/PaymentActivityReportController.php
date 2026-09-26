<?php

namespace App\Http\Controllers\Web;

use App\Models\Outlet;
use App\Services\PaymentActivityReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * "Account for all payment-related activity on the system" — every
 * shipment payment and refund, every wallet funding and transfer,
 * every cash settlement, in one filterable place, exportable to
 * Excel. See PaymentActivityReportService for why this is built as
 * a SQL UNION ALL rather than merged in PHP.
 */
class PaymentActivityReportController extends \App\Http\Controllers\Controller
{
    public function __construct(private PaymentActivityReportService $report)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $activity = $this->report->query($filters)->paginate(50)->withQueryString();

        return view('payment-activity.index', [
            'activity' => $activity,
            'outlets' => auth()->user()->hasGlobalAccess() || auth()->user()->hasRegionAccess()
                ? Outlet::orderBy('name')->get(['id', 'name'])
                : collect(),
            'filters' => $request->only(['date_from', 'date_to', 'type', 'outlet_id']),
        ]);
    }

    /**
     * Streamed via cursor() rather than a single get() — a report
     * covering every payment-related table could otherwise mean
     * loading tens of thousands of rows into one Collection at once.
     * cursor() reads one row at a time from the database, so memory
     * use stays flat regardless of how large the filtered result is.
     */
    public function export(Request $request): Response
    {
        $filters = $this->resolveFilters($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payment Activity');

        $headers = ['Date', 'Type', 'Amount', 'Method', 'Reference', 'Description', 'Shipment ID'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        $row = 2;
        foreach ($this->report->query($filters)->cursor() as $entry) {
            $sheet->setCellValueByColumnAndRow(1, $row, $entry->occurred_at);
            $sheet->setCellValueByColumnAndRow(2, $row, $entry->type);
            $sheet->setCellValueByColumnAndRow(3, $row, $entry->amount);
            $sheet->setCellValueByColumnAndRow(4, $row, $entry->method);
            $sheet->setCellValueByColumnAndRow(5, $row, $entry->reference);
            $sheet->setCellValueByColumnAndRow(6, $row, $entry->description);
            $sheet->setCellValueByColumnAndRow(7, $row, $entry->shipment_id);
            $row++;
        }

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'payment-activity') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download($path, 'payment-activity-' . now()->format('Ymd-His') . '.xlsx')->deleteFileAfterSend();
    }

    /**
     * Same access-scope narrowing already used everywhere in this
     * app — outlet-scoped staff only ever see their own outlet's
     * activity, hub/region-scoped staff see their own reach, global
     * access sees everything. Applied here rather than trusted to
     * the outlet_id filter alone, since that's just a UI convenience
     * for someone who can already see multiple outlets.
     */
    private function resolveFilters(Request $request): array
    {
        $user = auth()->user();

        $filters = $request->only(['date_from', 'date_to', 'type']);

        if ($user->hasOutletAccess()) {
            $filters['outlet_ids'] = [$user->outlet_id];
        } elseif (! $user->hasGlobalAccess() && ! $user->hasRegionAccess()) {
            $filters['outlet_ids'] = \App\Models\Outlet::whereIn('hub_id', $user->accessibleHubIds())->pluck('id')->all();
        } elseif ($request->filled('outlet_id')) {
            $filters['outlet_ids'] = [$request->input('outlet_id')];
        }

        return $filters;
    }
}
