<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mirrors EnsureStaffUser exactly, for the opposite user_type — the
 * client portal shares the same session guard the staff app already
 * uses (a standard, well-supported pattern: one guard, several kinds
 * of user, kept apart by middleware rather than by a second guard
 * and session cookie), so a staff login can never reach the client
 * portal and vice versa.
 */
class EnsureClientUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type !== 'client') {
            auth()->logout();

            return redirect()->route('portal.login')->withErrors(['email' => 'This account cannot access the client portal.']);
        }

        if (! $user->canSignIn()) {
            auth()->logout();

            return redirect()->route('portal.login')->withErrors(['email' => $this->messageFor($user->account_status, $user->status_reason)]);
        }

        return $next($request);
    }

    private function messageFor(string $status, ?string $reason): string
    {
        $base = match ($status) {
            'suspended' => 'This account has been suspended.',
            'locked' => 'This account has been locked.',
            'terminated' => 'This account has been terminated.',
            default => 'This account cannot access the client portal.',
        };

        return $reason ? "{$base} Reason: {$reason}" : $base;
    }
}
