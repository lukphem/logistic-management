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
        if (! $request->user() || $request->user()->user_type !== $type) {
            return response()->json(['message' => 'Forbidden for this user type'], 403);
        }

        return $next($request);
    }
}
