<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * A "statement" list, same design decision as
     * Api\ClientController::invoices() (Increment 10) — this app has no
     * separate invoice-document entity, just each shipment's own billing
     * breakdown. This is that same view, staff-facing across every
     * client rather than scoped to one.
     */
    public function index(Request $request): View
    {
        $query = Shipment::with(['clientUser', 'apiClient']);

        if ($request->filled('client_user_id')) {
            $query->where('client_user_id', $request->client_user_id);
        } elseif ($request->filled('api_client_id')) {
            $query->where('api_client_id', $request->api_client_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $shipments = $query->latest()->paginate(20)->withQueryString();

        return view('invoices.index', [
            'shipments' => $shipments,
            'portalClients' => User::where('user_type', 'client')->orderBy('name')->get(),
            'apiClients' => ApiClient::orderBy('name')->get(),
        ]);
    }
}
