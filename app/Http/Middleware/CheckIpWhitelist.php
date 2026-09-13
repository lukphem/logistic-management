<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies to external customer-facing API routes only (routes/api.php,
 * "client-integration" group) — not to internal staff/rider/portal JWT routes.
 *
 * Expects the request to carry the API key, e.g. header: X-Api-Key.
 */
class CheckIpWhitelist
{
    /**
     * Routes here are the WRITE side of the integration surface —
     * anything that creates, modifies, or cancels a real resource.
     * /quote is deliberately NOT here even though it's a POST: it only
     * calculates a price and doesn't persist a shipment, so a
     * read_only key can still generate quotes.
     */
    private const WRITE_ACTIONS = [
        'v1/integration/shipments',
        'v1/integration/webhooks/subscribe',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');
        $ip = $request->ip();

        if (! $apiKey) {
            return response()->json(['message' => 'Missing API key'], 401);
        }

        $client = ApiClient::where('api_key', $apiKey)->first();

        if (! $client || ! $client->is_active) {
            $this->logDenial(null, $ip, 'invalid_or_inactive_key', $request);
            return response()->json(['message' => 'Invalid or inactive API key'], 401);
        }

        if (! $client->isIpAllowed($ip)) {
            $this->logDenial($client->id, $ip, 'ip_not_whitelisted', $request);
            return response()->json(['message' => 'Request origin not authorized for this API key'], 403);
        }

        if ($client->isReadOnly() && $this->isWriteAction($request)) {
            $this->logDenial($client->id, $ip, 'read_only_key_attempted_write', $request);
            return response()->json(['message' => 'This API key is read-only and cannot perform this action.'], 403);
        }

        // /shipments/{id}/cancel isn't in WRITE_ACTIONS above (the
        // literal path includes the shipment's own {id}, so it can't
        // be matched by exact string) — matched separately here by
        // pattern instead.
        if ($client->isReadOnly() && $request->is('v1/integration/shipments/*/cancel')) {
            $this->logDenial($client->id, $ip, 'read_only_key_attempted_write', $request);
            return response()->json(['message' => 'This API key is read-only and cannot perform this action.'], 403);
        }

        $client->update(['last_used_at' => now(), 'last_used_ip' => $ip]);
        $request->attributes->set('api_client', $client);

        return $next($request);
    }

    private function isWriteAction(Request $request): bool
    {
        return in_array($request->path(), self::WRITE_ACTIONS, true);
    }

    private function logDenial(?int $apiClientId, string $ip, string $reason, Request $request): void
    {
        \App\Models\ApiAccessDenial::create([
            'api_client_id' => $apiClientId,
            'attempted_ip' => $ip,
            'reason' => $reason,
            'endpoint' => $request->path(),
        ]);

        Log::warning("API access denied: {$reason}", ['ip' => $ip, 'endpoint' => $request->path()]);
    }
}
