<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientAccount;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Individual signup only — an organization account is never created
 * directly here, it's requested afterward through the upgrade flow
 * (Step 3) and needs admin approval, per the request. Mirrors the
 * staff-side ClientController::store() creation shape (User, a
 * default ClientAccount, a linking ClientProfile) exactly, since
 * these need to end up in the same state a staff-created account
 * would — just without a staff member involved, and without the
 * organization-only fields that flow doesn't need here.
 */
class PortalRegisterController extends Controller
{
    public function show(): View
    {
        return view('portal.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'id_type' => 'required|in:national_id,passport,drivers_license,voters_card',
            'id_number' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => Hash::make($data['password']),
            'user_type' => 'client',
            'is_active' => true,
            'account_status' => 'active',
        ]);

        // No staff member involved, so the new client stands in for
        // their own "creator" here — generateAccountNumber() only
        // ever reads ->name/->staff_short_code off whatever's passed,
        // both of which exist on any User regardless of type.
        $account = ClientAccount::create([
            'client_user_id' => $user->id,
            'account_name' => 'Default Account',
            'account_number' => ClientAccount::generateAccountNumber(null, null, $user),
            'is_default' => true,
            'created_by' => $user->id,
            'account_type' => 'individual',
            'id_type' => $data['id_type'],
            'id_number' => $data['id_number'],
        ]);

        ClientProfile::create(['client_user_id' => $user->id, 'client_account_id' => $account->id]);

        Auth::login($user);

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
