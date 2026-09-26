<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientUpgradeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The client-facing half of the upgrade workflow — submits the same
 * organization fields the staff-side ClientController::upgrade()
 * applies directly, but this never touches the account itself.
 * Approving or rejecting what's submitted here is Step 4's job, from
 * the staff side.
 */
class PortalUpgradeController extends Controller
{
    public function show(): View
    {
        $account = auth()->user()->defaultAccount;

        return view('portal.upgrade', [
            'account' => $account,
            'pendingRequest' => $account ? $account->upgradeRequests()->where('status', 'pending')->latest()->first() : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = auth()->user()->defaultAccount;

        abort_if(! $account, 404);

        if ($account->account_type === 'organization') {
            return redirect()->route('portal.dashboard')->withErrors(['upgrade' => 'This account is already an organization account.']);
        }

        if ($account->upgradeRequests()->where('status', 'pending')->exists()) {
            return redirect()->route('portal.upgrade.show')->withErrors(['upgrade' => 'You already have a pending upgrade request — it needs to be reviewed before you can submit another.']);
        }

        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'rc_number' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_role' => 'nullable|string|max:255',
        ]);

        ClientUpgradeRequest::create([
            'client_account_id' => $account->id,
            ...$data,
        ]);

        return redirect()->route('portal.dashboard')->with('status', 'Upgrade request submitted — an admin will review it shortly.');
    }
}
