<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ClientProfile;
use App\Models\ClientServiceDiscount;
use App\Models\ClientSpecialTariff;
use App\Models\ClientSpecialTariffZonePrice;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = User::where('user_type', 'client')
            ->with('clientProfile')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.form', [
            'user' => new User(),
            'profile' => new ClientProfile(),
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateForm($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => Hash::make($data['password']),
            'user_type' => 'client',
            'is_active' => true,
            'account_status' => 'active',
        ]);

        ClientProfile::create([
            'client_user_id' => $user->id,
            ...$this->profileData($data),
        ]);

        return redirect()->route('clients.edit', $user)->with('status', 'Client account created.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->user_type === 'client', 404);

        return view('clients.form', [
            'user' => $user,
            'profile' => $user->clientProfile ?? new ClientProfile(),
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $data = $this->validateForm($request, $user->id);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            ...($data['password'] ? ['password' => Hash::make($data['password'])] : []),
        ]);

        ClientProfile::updateOrCreate(
            ['client_user_id' => $user->id],
            $this->profileData($data)
        );

        return redirect()->route('clients.edit', $user)->with('status', 'Client account updated.');
    }

    /**
     * The "management" screen — profile plus billing (per-service
     * discounts and special tariffs), all in one place rather than
     * scattered across separate pages, since a person configuring one
     * is very likely about to configure the other.
     */
    public function manage(User $user): View
    {
        abort_unless($user->user_type === 'client', 404);

        return view('clients.manage', [
            'user' => $user->load('clientProfile', 'billingProfile'),
            'serviceTypes' => ServiceType::where('is_active', true)->orderBy('name')->get(),
            'discounts' => ClientServiceDiscount::where('client_user_id', $user->id)->with('serviceType')->get()->keyBy('service_type_id'),
            'specialTariffs' => ClientSpecialTariff::where('client_user_id', $user->id)->with(['serviceType', 'zonePrices.zone'])->orderBy('service_type_id')->orderBy('min_weight')->get(),
            'zones' => Zone::where('applies_domestic', true)->orderBy('name')->get(),
        ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $user->delete();

        return redirect()->route('clients.index')->with('status', 'Client account removed.');
    }

    /**
     * Individual -> organization only, on purpose — see the
     * client_profiles migration's note on why this doesn't go the other
     * way in the UI (an org that "downgrades" would lose its RC/TIN
     * trail). Requires the organization fields to actually be filled in
     * as part of the same request, not just a bare status flip.
     */
    public function upgrade(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $profile = $user->clientProfile;
        abort_if(! $profile || $profile->account_type === 'organization', 404);

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'rc_number' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_role' => 'nullable|string|max:255',
        ]);
        $data = $validator->validate();

        $profile->update([...$data, 'account_type' => 'organization']);

        return redirect()->route('clients.edit', $user)->with('status', "{$user->name} upgraded to an organization account.");
    }

    public function storeDiscount(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);
        $data = $validator->validate();

        ClientServiceDiscount::updateOrCreate(
            ['client_user_id' => $user->id, 'service_type_id' => $data['service_type_id']],
            ['discount_percentage' => $data['discount_percentage']]
        );

        return back()->with('status', 'Discount saved.');
    }

    public function destroyDiscount(User $user, ClientServiceDiscount $discount): RedirectResponse
    {
        abort_unless($discount->client_user_id === $user->id, 404);

        $discount->delete();

        return back()->with('status', 'Discount removed — this service type now bills standard for this client.');
    }

    public function storeSpecialTariff(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'max_weight_limit' => 'required|numeric|min:0',
            'additional_weight' => 'required|numeric|min:0.01',
            'zone_prices' => 'required|array|min:1',
            'zone_prices.*.zone_id' => 'required|exists:zones,id',
            'zone_prices.*.charge' => 'required|numeric|min:0',
            'zone_prices.*.additional_charge' => 'nullable|numeric|min:0',
            'zone_prices.*.transit_days' => 'nullable|integer|min:0',
        ]);
        $data = $validator->validate();

        $tariff = ClientSpecialTariff::create([
            'client_user_id' => $user->id,
            'service_type_id' => $data['service_type_id'],
            'min_weight' => $data['min_weight'],
            'max_weight' => $data['max_weight'],
            'max_weight_limit' => $data['max_weight_limit'],
            'additional_weight' => $data['additional_weight'],
            'is_active' => true,
        ]);

        foreach ($data['zone_prices'] as $zonePrice) {
            ClientSpecialTariffZonePrice::create([
                'client_special_tariff_id' => $tariff->id,
                'zone_id' => $zonePrice['zone_id'],
                'charge' => $zonePrice['charge'],
                'additional_charge' => $zonePrice['additional_charge'] ?? 0,
                'transit_days' => $zonePrice['transit_days'] ?? null,
            ]);
        }

        return back()->with('status', 'Special rate added.');
    }

    public function destroySpecialTariff(User $user, ClientSpecialTariff $tariff): RedirectResponse
    {
        abort_unless($tariff->client_user_id === $user->id, 404);

        $tariff->delete();

        return back()->with('status', 'Special rate removed — this weight band now bills standard for this client.');
    }

    private function profileData(array $data): array
    {
        return [
            'account_type' => $data['account_type'],
            'id_type' => $data['account_type'] === 'individual' ? ($data['id_type'] ?? null) : null,
            'id_number' => $data['account_type'] === 'individual' ? ($data['id_number'] ?? null) : null,
            'company_name' => $data['account_type'] === 'organization' ? ($data['company_name'] ?? null) : null,
            'rc_number' => $data['account_type'] === 'organization' ? ($data['rc_number'] ?? null) : null,
            'tin' => $data['account_type'] === 'organization' ? ($data['tin'] ?? null) : null,
            'industry' => $data['account_type'] === 'organization' ? ($data['industry'] ?? null) : null,
            'contact_person_name' => $data['account_type'] === 'organization' ? ($data['contact_person_name'] ?? null) : null,
            'contact_person_role' => $data['account_type'] === 'organization' ? ($data['contact_person_role'] ?? null) : null,
            'address' => $data['address'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'alternate_phone' => $data['alternate_phone'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
        ];
    }

    private function validateForm(Request $request, ?int $ignoreUserId = null): array
    {
        $accountType = $request->input('account_type', 'individual');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($ignoreUserId ? ",{$ignoreUserId}" : ''),
            'phone_number' => 'required|string|max:30',
            'password' => $ignoreUserId ? 'nullable|string|min:8' : 'required|string|min:8',
            'account_type' => 'required|in:individual,organization',

            // Individual — compulsory only when creating an individual account.
            'id_type' => $accountType === 'individual' ? 'required|in:national_id,passport,drivers_license,voters_card' : 'nullable',
            'id_number' => $accountType === 'individual' ? 'required|string|max:255' : 'nullable|string|max:255',

            // Organization — compulsory only when creating an organization account directly.
            // (Upgrading an existing individual goes through upgrade() instead, not here.)
            'company_name' => $accountType === 'organization' ? 'required|string|max:255' : 'nullable|string|max:255',
            'rc_number' => $accountType === 'organization' ? 'required|string|max:255' : 'nullable|string|max:255',
            'tin' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'contact_person_name' => $accountType === 'organization' ? 'required|string|max:255' : 'nullable|string|max:255',
            'contact_person_role' => 'nullable|string|max:255',

            'address' => 'nullable|string|max:1000',
            'city_id' => 'nullable|exists:cities,id',
            'alternate_phone' => 'nullable|string|max:30',
            'billing_address' => 'nullable|string|max:1000',
        ]);

        return $validator->validate();
    }
}
