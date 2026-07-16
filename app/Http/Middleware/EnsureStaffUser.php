<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session-guard equivalent of the API's UserType middleware — the admin
 * dashboard is staff-only. Riders and clients never get a web session;
 * they authenticate via Sanctum tokens against the API instead.
 */
class EnsureStaffUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type !== 'staff' || ! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'This account cannot access the admin dashboard.']);
        }

        return $next($request);
    }
}
