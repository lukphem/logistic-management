<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    /**
     * External-integration only (API key). Registers a callback URL and
     * the events it wants pushed to it. Actual dispatch (HMAC-signed POST
     * on shipment status change) is a queued job to be added alongside the
     * notification service in a later increment — this endpoint only
     * manages the subscription record.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:shipment.status_changed,shipment.delivered,shipment.exception,shipment.cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $apiClient = $request->attributes->get('api_client');

        $subscription = WebhookSubscription::create([
            'api_client_id' => $apiClient->id,
            'url' => $request->url,
            'events' => $request->events,
            'secret' => Str::random(40),
        ]);

        return response()->json([
            'id' => $subscription->id,
            'url' => $subscription->url,
            'events' => $subscription->events,
            'secret' => $subscription->secret, // shown once at creation, for the client to store and verify signatures with
        ], 201);
    }
}
