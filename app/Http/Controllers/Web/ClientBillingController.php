<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\ClientBillingProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ClientBillingController extends Controller
{
    /**
     * Lists every client (portal users + external api_clients) side by
     * side with their current billing status. Anyone without a
     * client_billing_profiles row is implicitly 'standard' — no row is
     * created until someone is actually put on a special rate.
     */
    public function index(): View
    {
        $portalClients = User::where('user_type', 'client')
            ->with('billingProfile')
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'type' => 'portal',
                'id' => $user->id,
                'name' => $user->name,
                'identifier' => $user->email,
                'profile' => $user->billingProfile,
            ]);

        $apiClients = ApiClient::with('billingProfile')
            ->orderBy('name')
            ->get()
            ->map(fn ($client) => [
                'type' => 'api',
                'id' => $client->id,
                'name' => $client->name,
                'identifier' => 'API — ' . $client->api_key,
                'profile' => $client->billingProfile,
            ]);

        $clients = $portalClients->concat($apiClients)->sortBy('name')->values();

        return view('client-billing.index', compact('clients'));
    }

    public function edit(string $type, int $id): View
    {
        $subject = $this->resolveSubject($type, $id);
        $profile = $subject['profile'];

        return view('client-billing.edit', [
            'type' => $type,
            'id' => $id,
            'name' => $subject['name'],
            'profile' => $profile,
        ]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'billing_type' => 'required|in:standard,special',
            'discount_percentage' => 'required_if:billing_type,special|nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $validator->validate();
        $data = $validator->validated();

        // A 'standard' client always has a 0 discount, regardless of what
        // was previously saved — switching back to standard should never
        // leave a stale discount lying around that resurfaces later.
        $data['discount_percentage'] = $data['billing_type'] === 'special'
            ? $data['discount_percentage']
            : 0;

        $subject = $this->resolveSubject($type, $id);

        ClientBillingProfile::updateOrCreate(
            $type === 'portal' ? ['client_user_id' => $id] : ['api_client_id' => $id],
            $data
        );

        return redirect()->route('client-billing.index')->with('status', "Billing updated for {$subject['name']}.");
    }

    private function resolveSubject(string $type, int $id): array
    {
        if ($type === 'portal') {
            $user = User::where('user_type', 'client')->findOrFail($id);

            return ['name' => $user->name, 'profile' => $user->billingProfile];
        }

        $apiClient = ApiClient::findOrFail($id);

        return ['name' => $apiClient->name, 'profile' => $apiClient->billingProfile];
    }
}
