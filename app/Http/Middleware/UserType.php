<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Register in app/Http/Kernel.php $middlewareAliases as 'user_type' => UserType::class,
 * Usage: ->middleware('user_type:staff') / ('user_type:rider') / ('user_type:client')
 */
class UserType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type !== $type) {
            return response()->json(['message' => 'Forbidden for this user type'], 403);
        }

        // Checked on every request, not just at login — suspending or
        // terminating someone mid-session should take effect immediately,
        // not only once their existing Sanctum token happens to expire.
        if (! $user->canSignIn()) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'This account no longer has access.',
                'account_status' => $user->account_status,
            ], 403);
        }

        return $next($request);
    }
}
