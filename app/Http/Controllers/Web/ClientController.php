<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\City;
use App\Models\ClientDocument;
use App\Models\ClientProfile;
use App\Models\ClientServiceDiscount;
use App\Models\ClientServiceSubscription;
use App\Models\ClientSpecialTariff;
use App\Models\ClientSpecialTariffZonePrice;
use App\Models\Country;
use App\Models\Department;
use App\Models\IpWhitelist;
use App\Models\ServiceType;
use App\Models\State;
use App\Models\Territory;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'countries' => Country::orderBy('name')->get(),
            'states' => State::orderBy('name')->get(),
            'territories' => Territory::orderBy('name')->get(),
            'staffUsers' => User::where('user_type', 'staff')->orderBy('name')->get(),
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
            'account_number' => str_pad((string) $user->id, 10, '0', STR_PAD_LEFT),
            'created_by' => auth()->id(),
            ...$this->profileData($data),
        ]);

        return redirect()->route('clients.show', $user)->with('status', 'Client account created.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->user_type === 'client', 404);

        return view('clients.form', [
            'user' => $user,
            'profile' => $user->clientProfile ?? new ClientProfile(),
            'cities' => City::orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'states' => State::orderBy('name')->get(),
            'territories' => Territory::orderBy('name')->get(),
            'staffUsers' => User::where('user_type', 'staff')->orderBy('name')->get(),
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

        return redirect()->route('clients.show', $user)->with('status', 'Client account updated.');
    }

    /**
     * The tabbed client hub — Overview, Transactions, Tariff, Discount,
     * Department, User, Service, Document, Security, Managerial
     * services, all in one place rather than scattered across separate
     * pages. Loads everything every tab could need up front (this page
     * is visited far less often than, say, the shipments list, so one
     * slightly heavier load beats N separate round trips as staff
     * click between tabs).
     */
    public function show(User $user): View
    {
        abort_unless($user->user_type === 'client', 404);

        $profile = $user->clientProfile;
        $isOrganization = $profile?->isOrganization() ?? false;

        return view('clients.show', [
            'user' => $user->load('clientProfile.city', 'clientProfile.country', 'clientProfile.state', 'clientProfile.territory', 'clientProfile.createdBy', 'clientProfile.businessManager', 'billingProfile'),
            'profile' => $profile,
            'isOrganization' => $isOrganization,
            'serviceTypes' => ServiceType::where('is_active', true)->orderBy('name')->get(),
            'discounts' => ClientServiceDiscount::where('client_user_id', $user->id)->with('serviceType')->get()->keyBy('service_type_id'),
            'specialTariffs' => ClientSpecialTariff::where('client_user_id', $user->id)->with(['serviceType', 'zonePrices.zone'])->orderBy('service_type_id')->orderBy('min_weight')->get(),
            'zones' => Zone::where('applies_domestic', true)->orderBy('name')->get(),
            'subscriptions' => ClientServiceSubscription::where('client_user_id', $user->id)->pluck('is_active', 'service_type_id'),
            'departments' => $isOrganization ? Department::where('client_user_id', $user->id)->orderBy('name')->get() : collect(),
            'subUsers' => $isOrganization ? User::whereHas('clientProfile', fn ($q) => $q->where('parent_client_user_id', $user->id))->with('clientProfile.department')->orderBy('name')->get() : collect(),
            'documents' => ClientDocument::where('client_user_id', $user->id)->latest()->get(),
            'apiClient' => ApiClient::where('client_user_id', $user->id)->with('ipWhitelists', 'webhookSubscriptions')->first(),
            'shipments' => \App\Models\Shipment::where('client_user_id', $user->id)->latest()->limit(25)->get(),
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

        return redirect()->route('clients.show', $user)->with('status', "{$user->name} upgraded to an organization account.");
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

        return redirect()->route('clients.show', $user)->with('status', 'Discount saved.');
    }

    public function destroyDiscount(User $user, ClientServiceDiscount $discount): RedirectResponse
    {
        abort_unless($discount->client_user_id === $user->id, 404);

        $discount->delete();

        return redirect()->route('clients.show', $user)->with('status', 'Discount removed — this service type now bills standard for this client.');
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

        return redirect()->route('clients.show', $user)->with('status', 'Special rate added.');
    }

    public function destroySpecialTariff(User $user, ClientSpecialTariff $tariff): RedirectResponse
    {
        abort_unless($tariff->client_user_id === $user->id, 404);

        $tariff->delete();

        return redirect()->route('clients.show', $user)->with('status', 'Special rate removed — this weight band now bills standard for this client.');
    }

    // ---------------------------------------------------------------
    // Department
    // ---------------------------------------------------------------

    public function storeDepartment(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client' && $user->clientProfile?->isOrganization(), 404);

        $data = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ])->validate();

        Department::create(['client_user_id' => $user->id, 'name' => $data['name']]);

        return redirect()->route('clients.show', $user)->with('status', 'Department added.');
    }

    public function destroyDepartment(User $user, Department $department): RedirectResponse
    {
        abort_unless($department->client_user_id === $user->id, 404);

        $department->delete();

        return redirect()->route('clients.show', $user)->with('status', 'Department removed.');
    }

    // ---------------------------------------------------------------
    // Sub-users (organization only) — real, separate logins
    // ---------------------------------------------------------------

    public function storeSubUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client' && $user->clientProfile?->isOrganization(), 404);

        $data = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:30',
            'password' => 'required|string|min:8',
            'department_id' => 'nullable|exists:departments,id',
        ])->validate();

        // A department picked here must actually belong to THIS
        // organization — exists:departments,id alone can't enforce
        // that, just that the id exists at all.
        if (! empty($data['department_id'])) {
            abort_unless(Department::where('id', $data['department_id'])->where('client_user_id', $user->id)->exists(), 422);
        }

        $subUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => Hash::make($data['password']),
            'user_type' => 'client',
            'is_active' => true,
            'account_status' => 'active',
        ]);

        ClientProfile::create([
            'client_user_id' => $subUser->id,
            'account_number' => str_pad((string) $subUser->id, 10, '0', STR_PAD_LEFT),
            'created_by' => auth()->id(),
            'account_type' => 'individual',
            'parent_client_user_id' => $user->id,
            'department_id' => $data['department_id'] ?? null,
        ]);

        return redirect()->route('clients.show', $user)->with('status', "{$subUser->name} added as a user under {$user->name}.");
    }

    public function destroySubUser(User $user, User $subUser): RedirectResponse
    {
        abort_unless($subUser->clientProfile?->parent_client_user_id === $user->id, 404);

        $subUser->delete();

        return redirect()->route('clients.show', $user)->with('status', 'User removed.');
    }

    // ---------------------------------------------------------------
    // Service subscriptions — access, not pricing
    // ---------------------------------------------------------------

    public function storeServiceSubscription(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $data = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'is_active' => 'sometimes|boolean',
        ])->validate();

        ClientServiceSubscription::updateOrCreate(
            ['client_user_id' => $user->id, 'service_type_id' => $data['service_type_id']],
            ['is_active' => $request->boolean('is_active')]
        );

        return redirect()->route('clients.show', $user)->with('status', 'Service access updated.');
    }

    // ---------------------------------------------------------------
    // Documents
    // ---------------------------------------------------------------

    public function storeDocument(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $data = Validator::make($request->all(), [
            'document_type' => 'required|in:' . implode(',', array_keys(ClientDocument::DOCUMENT_TYPES)),
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ])->validate();

        $file = $request->file('file');
        $path = $file->store('client-documents/' . $user->id, 'public');

        ClientDocument::create([
            'client_user_id' => $user->id,
            'document_type' => $data['document_type'],
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('clients.show', $user)->with('status', 'Document uploaded.');
    }

    public function destroyDocument(User $user, ClientDocument $document): RedirectResponse
    {
        abort_unless($document->client_user_id === $user->id, 404);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('clients.show', $user)->with('status', 'Document removed.');
    }

    // ---------------------------------------------------------------
    // Security — API access (reuses the same ApiClient/IpWhitelist/
    // WebhookSubscription system built for external integration
    // partners, just linked to this client's own account instead)
    // ---------------------------------------------------------------

    public function generateApiAccess(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $result = ApiClient::generateFor($user->id, $user->clientProfile?->company_name ?: $user->name);

        // The plaintext secret only ever exists in this one response -
        // flashed to session for a single display, never persisted or
        // logged anywhere.
        return redirect()->route('clients.show', $user)
            ->with('status', 'API access generated — copy the secret now, it will not be shown again.')
            ->with('plaintext_api_secret', $result['plaintext_secret']);
    }

    public function updateApiSettings(Request $request, User $user): RedirectResponse
    {
        $apiClient = ApiClient::where('client_user_id', $user->id)->firstOrFail();

        $data = Validator::make($request->all(), [
            'api_response_format' => 'required|in:url,base64',
            'ip_whitelist_enabled' => 'sometimes|boolean',
            'rate_limit_per_minute' => 'required|integer|min:1|max:6000',
        ])->validate();

        $apiClient->update([
            'api_response_format' => $data['api_response_format'],
            'ip_whitelist_enabled' => $request->boolean('ip_whitelist_enabled'),
            'rate_limit_per_minute' => $data['rate_limit_per_minute'],
        ]);

        return redirect()->route('clients.show', $user)->with('status', 'API settings updated.');
    }

    public function storeIpWhitelist(Request $request, User $user): RedirectResponse
    {
        $apiClient = ApiClient::where('client_user_id', $user->id)->firstOrFail();

        $data = Validator::make($request->all(), [
            'ip_or_cidr' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
        ])->validate();

        IpWhitelist::create([
            'api_client_id' => $apiClient->id,
            'ip_or_cidr' => $data['ip_or_cidr'],
            'label' => $data['label'] ?? null,
            'added_at' => now(),
        ]);

        return redirect()->route('clients.show', $user)->with('status', 'IP added to whitelist.');
    }

    public function destroyIpWhitelist(User $user, IpWhitelist $ipWhitelist): RedirectResponse
    {
        abort_unless($ipWhitelist->apiClient?->client_user_id === $user->id, 404);

        $ipWhitelist->delete();

        return redirect()->route('clients.show', $user)->with('status', 'IP removed from whitelist.');
    }

    public function storeWebhook(Request $request, User $user): RedirectResponse
    {
        $apiClient = ApiClient::where('client_user_id', $user->id)->firstOrFail();

        $data = Validator::make($request->all(), [
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
        ])->validate();

        WebhookSubscription::create([
            'api_client_id' => $apiClient->id,
            'url' => $data['url'],
            'events' => $data['events'],
            'secret' => \Illuminate\Support\Str::random(32),
            'is_active' => true,
        ]);

        return redirect()->route('clients.show', $user)->with('status', 'Webhook added.');
    }

    public function destroyWebhook(User $user, WebhookSubscription $webhook): RedirectResponse
    {
        abort_unless($webhook->apiClient?->client_user_id === $user->id, 404);

        $webhook->delete();

        return redirect()->route('clients.show', $user)->with('status', 'Webhook removed.');
    }

    // ---------------------------------------------------------------
    // Managerial services — warehouse/COD access, insurance agreement,
    // invoice terms, SLA commitments
    // ---------------------------------------------------------------

    public function updateManagerial(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $data = Validator::make($request->all(), [
            'warehouse_access' => 'sometimes|boolean',
            'cod_enabled' => 'sometimes|boolean',
            'insurance_agreement' => 'sometimes|boolean',
            'insurance_agreement_date' => 'nullable|date',
            'insurance_agreement_notes' => 'nullable|string|max:2000',
            'invoice_due_days' => 'nullable|integer|min:0|max:365',
            'sla_pickup_hours' => 'nullable|integer|min:0|max:720',
            'sla_delivery_days' => 'nullable|integer|min:0|max:90',
        ])->validate();

        $data['warehouse_access'] = $request->boolean('warehouse_access');
        $data['cod_enabled'] = $request->boolean('cod_enabled');
        $data['insurance_agreement'] = $request->boolean('insurance_agreement');

        ClientProfile::updateOrCreate(['client_user_id' => $user->id], $data);

        return redirect()->route('clients.show', $user)->with('status', 'Managerial settings updated.');
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
            'industry' => $data['industry'] ?? null,
            'contact_person_name' => $data['account_type'] === 'organization' ? ($data['contact_person_name'] ?? null) : null,
            'contact_person_role' => $data['account_type'] === 'organization' ? ($data['contact_person_role'] ?? null) : null,
            'address' => $data['address'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'state_id' => $data['state_id'] ?? null,
            'territory_id' => $data['territory_id'] ?? null,
            'express_center' => $data['express_center'] ?? null,
            'business_objective' => $data['business_objective'] ?? null,
            'alternate_phone' => $data['alternate_phone'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'business_manager_id' => $data['business_manager_id'] ?? null,
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
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'territory_id' => 'nullable|exists:territories,id',
            'express_center' => 'nullable|string|max:255',
            'business_objective' => 'nullable|string|max:2000',
            'alternate_phone' => 'nullable|string|max:30',
            'billing_address' => 'nullable|string|max:1000',
            'business_manager_id' => 'nullable|exists:users,id',
        ]);

        return $validator->validate();
    }
}
