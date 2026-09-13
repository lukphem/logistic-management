<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ScanStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ScanStatusController extends Controller
{
    public function index(): View
    {
        $scanStatuses = ScanStatus::all(); // already ordered by sort_order via model global scope

        return view('scan-statuses.index', compact('scanStatuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:100|alpha_dash|unique:scan_statuses,key',
            'label' => 'required|string|max:100',
            'is_terminal' => 'sometimes|boolean',
        ]);

        $validator->validate();

        $data = $validator->validated();
        $data['is_terminal'] = $request->boolean('is_terminal');
        $data['sort_order'] = (ScanStatus::withoutGlobalScopes()->max('sort_order') ?? 0) + 1;

        ScanStatus::create($data);

        return redirect()->route('scan-statuses.index')->with('status', 'Scan status added.');
    }

    public function update(Request $request, ScanStatus $scanStatus): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:100',
            'sort_order' => 'required|integer|min:0',
            'is_terminal' => 'sometimes|boolean',
        ]);

        $validator->validate();

        $data = $validator->validated();
        $data['is_terminal'] = $request->boolean('is_terminal');

        // key is intentionally not editable — it's what's stored on
        // historical shipments/scan_events, so renaming it would silently
        // orphan past records. Only the display label and ordering change.
        $scanStatus->update($data);

        return redirect()->route('scan-statuses.index')->with('status', 'Scan status updated.');
    }

    public function destroy(ScanStatus $scanStatus): RedirectResponse
    {
        // Same reasoning as update()'s comment on why key is
        // immutable: the key is what's stored on historical shipments
        // and scan_events, as a plain string, not a foreign key —
        // deleting a status still in use wouldn't fail loudly, it
        // would just silently strip the label/terminal-flag from
        // every past and current record using it. Blocks instead,
        // the same "can't remove while in use" pattern used elsewhere
        // in this app.
        $inUseByShipments = \App\Models\Shipment::where('current_status', $scanStatus->key)->exists();
        $inUseByScanEvents = \App\Models\ScanEvent::where('status', $scanStatus->key)->exists();

        if ($inUseByShipments || $inUseByScanEvents) {
            return redirect()->route('scan-statuses.index')->withErrors(['scan_status' => "Can't remove \"{$scanStatus->label}\" — it's still referenced by existing shipments or scan history."]);
        }

        $scanStatus->delete();

        return redirect()->route('scan-statuses.index')->with('status', 'Scan status removed.');
    }
}
