<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalVerificationController extends Controller
{
    public function notice(): View
    {
        return view('portal.auth.verify-email');
    }

    /**
     * Reached via the signed link the verification email actually
     * contains — EmailVerificationRequest validates that signature
     * and that the id/hash in the URL match the logged-in user
     * before this ever runs.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('portal.dashboard');
        }

        $request->fulfill();

        event(new Verified($request->user()));

        return redirect()->route('portal.dashboard')->with('status', 'Email verified — welcome in.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('portal.dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Verification link sent — check your inbox.');
    }
}
