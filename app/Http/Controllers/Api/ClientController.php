<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientBillingProfile;
use App\Models\ClientWallet;
use App\Models\Shipment;
use App\Services\ShipmentPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function __construct(private ShipmentPricingService $pricingService)
    {
    }

    /**
     * Returns a price quote without creating a shipment. Used identically
     * by the client portal (JWT) and external integrators (API key) —
     * see routes/api.php: /client/quote and /integration/quote both point
     * here.
     *
     * base_amount is currently always 0 — the billing-model calculation
     * layer was cleared and is being rebuilt one model at a time (see
     * ShipmentPricingService). Everything else (discount resolution,
     * insurance, onforwarding, VAT) already works correctly.
     */
    public function quote(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'origin_zone_id' => 'nullable|exists:zones,id',
            'destination_zone_id' => 'nullable|exists:zones,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'origin_district_id' => 'nullable|exists:districts,id',
            'destination_city_id' => 'nullable|exists:cities,id',
            'destination_district_id' => 'nullable|exists:districts,id',
            'distance_km' => 'nullable|numeric',
            'weight_kg' => 'nullable|numeric',
            'quantity' => 'nullable|integer|min:1',
            'carton_size' => 'nullable|in:small,medium,large',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'insured' => 'sometimes|boolean',
            'declared_value' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $breakdown = $this->pricingService->priceShipment(
            $validator->validated(),
            ClientBillingProfile::resolveForRequest($request)
        );

        return response()->json([
            'service_type_id' => (int) $request->service_type_id,
            ...$breakdown,
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        // No dedicated invoices table yet — an "invoice" here is simply a
        // paginated statement of a client's own shipments and their
        // charges. Revisit if formal invoice documents (PDF, sequential
        // numbering) are required.
        $query = Shipment::query();

        if ($request->user()) {
            $query->where('client_user_id', $request->user()->id);
        } elseif ($apiClient = $request->attributes->get('api_client')) {
            $query->where('api_client_id', $apiClient->id);
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 20))
        );
    }

    public function wallet(Request $request): JsonResponse
    {
        $wallet = $this->resolveWallet($request);

        return response()->json($wallet->load(['transactions' => fn ($q) => $q->latest()->limit(20)]));
    }

    private function resolveWallet(Request $request): ClientWallet
    {
        if ($request->user()) {
            return ClientWallet::firstOrCreate(['user_id' => $request->user()->id]);
        }

        $apiClient = $request->attributes->get('api_client');

        return ClientWallet::firstOrCreate(['api_client_id' => $apiClient->id]);
    }
}
