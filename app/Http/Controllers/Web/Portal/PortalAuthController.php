<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function show(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials don\'t match our records.'])->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->user_type !== 'client') {
            Auth::logout();

            return back()->withErrors(['email' => 'This account cannot access the client portal.']);
        }

        if (! $user->canSignIn()) {
            Auth::logout();

            $messages = [
                'suspended' => 'This account has been suspended.',
                'locked' => 'This account has been locked.',
                'terminated' => 'This account has been terminated.',
            ];
            $message = $messages[$user->account_status] ?? 'This account cannot access the client portal.';
            $message .= $user->status_reason ? " Reason: {$user->status_reason}" : '';

            return back()->withErrors(['email' => $message]);
        }

        $request->session()->regenerate();

        // Login itself succeeds even while unverified — the session
        // exists so the person can actually reach the "please verify"
        // page and its resend button. What's actually gated is
        // everything past that (the dashboard and the rest of the
        // portal), via the 'verified' middleware on those routes,
        // which redirects here to verification.notice instead.
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('portal.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
