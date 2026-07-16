<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RateCard;
use App\Models\ZoneRateMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RateCard::query();

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'service_type' => 'required|string',
            'billing_model' => 'required|in:flat,distance,zone_to_zone,weight,volumetric,hybrid,service_multiplier,time_surcharge,contract',
            'model_config' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(RateCard::create($validator->validated()), 201);
    }

    public function show(RateCard $rate): JsonResponse
    {
        return response()->json($rate->load('zoneMatrixEntries'));
    }

    public function update(Request $request, RateCard $rate): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'model_config' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rate->update($validator->validated());

        return response()->json($rate);
    }

    public function destroy(RateCard $rate): JsonResponse
    {
        $rate->delete();

        return response()->json(['message' => 'Rate card removed']);
    }

    /**
     * Upsert one zone-to-zone matrix entry. Kept as a dedicated action
     * rather than a nested resource since a matrix entry is only ever
     * created/updated one pair at a time from the setup UI.
     */
    public function setZonePrice(Request $request, RateCard $rate): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_zone_id' => 'required|exists:zones,id',
            'destination_zone_id' => 'required|exists:zones,id',
            'price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $entry = ZoneRateMatrix::updateOrCreate(
            [
                'rate_card_id' => $rate->id,
                'origin_zone_id' => $request->origin_zone_id,
                'destination_zone_id' => $request->destination_zone_id,
            ],
            ['price' => $request->price]
        );

        return response()->json($entry, 201);
    }
}
