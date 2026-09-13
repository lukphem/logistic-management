<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\City;
use App\Models\ClientAccount;
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

/**
 * Client -> Account restructure: a Client (this controller's $user,
 * user_type='client') can have multiple Accounts (Lagos, Abuja,
 * E-commerce). Everything that's genuinely account-level — type,
 * contact details, products, billing, Business Manager — lives on
 * ClientAccount, not on the client login itself. This controller
 * currently operates on each client's single "Default Account" for
 * every account-level action (create/edit/discounts/special
 * tariffs/departments/sub-users/service access/managerial settings) —
 * full multi-account creation/switching UI is the next phase; this
 * phase makes the underlying data model and resolution logic
 * correctly account-based first, without changing what a single-
 * account client experiences today.
 *
 * Documents and API access (Security tab) stay client-level, not
 * per-account — a signed business agreement and an API integration
 * belong to the company as a whole, not to one operational branch.
 */
class ClientController extends Controller
{
    public function __construct(private \App\Services\CsvService $csv)
    {
    }

    public function index(Request $request): View
    {
        $clients = User::where('user_type', 'client')
            ->with('defaultAccount')
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
            'account' => new ClientAccount(),
            'profile' => new ClientAccount(), // view compatibility — clients/form.blade.php reads $profile
            'cities' => City::orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'states' => State::with('territory')->orderBy('name')->get(),
            'territories' => Territory::orderBy('name')->get(),
            'outlets' => \App\Models\Outlet::where('is_active', true)->orderBy('name')->get(),
            'staffUsers' => User::where('user_type', 'staff')->orderBy('name')->get(),
            'allowManualAccountNumber' => \App\Models\Setting::current()->allow_manual_account_number,
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

        $accountFields = $this->accountData($request, $data, null);
        $creator = auth()->user();

        $account = ClientAccount::create([
            'client_user_id' => $user->id,
            'account_name' => 'Default Account',
            'account_number' => $this->resolveAccountNumber(
                $request,
                $accountFields['state_id'] ? State::find($accountFields['state_id']) : null,
                $accountFields['outlet_id'] ? \App\Models\Outlet::find($accountFields['outlet_id']) : null,
                $creator
            ),
            'is_default' => true,
            'created_by' => $creator->id,
            ...$accountFields,
        ]);

        // Links this login to its own Default Account — mirrors how a
        // sub-user's ClientProfile links them to the account they work
        // under, so both are reached the same way.
        ClientProfile::create(['client_user_id' => $user->id, 'client_account_id' => $account->id]);

        return redirect()->route('clients.show', $user)->with('status', 'Client account created.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->user_type === 'client', 404);

        return view('clients.form', [
            'user' => $user,
            'account' => $user->defaultAccount ?? new ClientAccount(),
            'profile' => $user->defaultAccount ?? new ClientAccount(), // view compatibility — clients/form.blade.php reads $profile
            'cities' => City::orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'states' => State::with('territory')->orderBy('name')->get(),
            'territories' => Territory::orderBy('name')->get(),
            'outlets' => \App\Models\Outlet::where('is_active', true)->orderBy('name')->get(),
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

        $account = $user->defaultAccount;

        if ($account) {
            // account_number is never regenerated on update — it's a
            // fixed identifier once assigned, not something that
            // should change just because the client's address changed.
            $account->update($this->accountData($request, $data, $account));
        } else {
            // Defensive — every client should already have a Default
            // Account (auto-created at store() time, or by the
            // Client -> Account restructure's backfill for anyone
            // created before it existed).
            $accountFields = $this->accountData($request, $data, null);
            $creator = auth()->user();

            $account = ClientAccount::create([
                'client_user_id' => $user->id,
                'account_name' => 'Default Account',
                'account_number' => $this->resolveAccountNumber(
                    $request,
                    $accountFields['state_id'] ? State::find($accountFields['state_id']) : null,
                    $accountFields['outlet_id'] ? \App\Models\Outlet::find($accountFields['outlet_id']) : null,
                    $creator
                ),
                'is_default' => true,
                'created_by' => $creator->id,
                ...$accountFields,
            ]);
            ClientProfile::updateOrCreate(['client_user_id' => $user->id], ['client_account_id' => $account->id]);
        }

        return redirect()->route('clients.show', $user)->with('status', 'Client account updated.');
    }

    /**
     * The tabbed client hub — Overview, Accounts, Transactions, Tariff,
     * Discount, Department, User, Service, Document, Security,
     * Managerial services, all in one place rather than scattered
     * across separate pages. Loads everything every tab could need up
     * front (this page is visited far less often than, say, the
     * shipments list, so one slightly heavier load beats N separate
     * round trips as staff click between tabs).
     *
     * $account is optional — /clients/{user} shows the Default
     * Account (unchanged); /clients/{user}/accounts/{account} shows
     * any specific one directly, satisfying "view all accounts, view
     * account-related information" without requiring a switch first.
     * Only the account currently marked default is writable from this
     * page (see $isViewingDefault in the view) — the write actions
     * (storeDiscount, storeSpecialTariff, etc.) all still target
     * whichever account is default, so viewing a non-default account
     * here is read-only until it's switched to.
     */
    public function show(User $user, ?ClientAccount $account = null): View
    {
        abort_unless($user->user_type === 'client', 404);

        if ($account) {
            abort_unless($account->client_user_id === $user->id, 404);
            $account->load('city', 'country', 'state', 'territory', 'createdBy', 'businessManager');
        } else {
            $account = $user->defaultAccount()->with('city', 'country', 'state', 'territory', 'createdBy', 'businessManager')->first();
        }

        $isOrganization = $account?->isOrganization() ?? false;
        $accountId = $account?->id;

        return view('clients.show', [
            'user' => $user->load('billingProfile'),
            'account' => $account,
            'profile' => $account, // kept for view compatibility during the transition
            'isOrganization' => $isOrganization,
            'isViewingDefault' => $account?->is_default ?? true,
            'activeTab' => request('tab', 'overview'),
            'serviceTypes' => ServiceType::where('is_active', true)->orderBy('name')->get(),
            'billingModels' => \App\Models\Setting::current()->supportedBillingModels(),
            'discounts' => ClientServiceDiscount::where('client_account_id', $accountId)->with('serviceType')->get()->keyBy('service_type_id'),
            'specialTariffs' => ClientSpecialTariff::where('client_account_id', $accountId)->with(['serviceType', 'zonePrices.zone'])->orderBy('service_type_id')->orderBy('min_weight')->get(),
            'odTariffs' => \App\Models\ClientOriginDestinationTariff::where('client_account_id', $accountId)
                ->with(['serviceType', 'originState', 'originCity', 'originCountry', 'destinationState', 'destinationCity', 'destinationCountry'])
                ->orderBy('service_type_id')->get(),
            'fleetTariffs' => \App\Models\ClientFleetBillingTariff::where('client_account_id', $accountId)
                ->with(['serviceType', 'vehicleType', 'originState', 'originCity', 'originCountry', 'destinationState', 'destinationCity', 'destinationCountry'])
                ->orderBy('service_type_id')->get(),
            'vehicleTypes' => \App\Models\VehicleType::where('is_active', true)->orderBy('name')->get(),
            'billingStates' => State::orderBy('name')->get(),
            'billingCountries' => Country::orderBy('name')->get(),
            'zones' => Zone::where('applies_domestic', true)->orderBy('name')->get(),
            'subscriptions' => ClientServiceSubscription::where('client_account_id', $accountId)->pluck('is_active', 'service_type_id'),
            'departments' => $isOrganization ? Department::where('client_account_id', $accountId)->orderBy('name')->get() : collect(),
            'subUsers' => $isOrganization ? User::whereHas('clientProfile', fn ($q) => $q->where('client_account_id', $accountId))->with('clientProfile.department')->orderBy('name')->get() : collect(),
            'documents' => ClientDocument::where('client_user_id', $user->id)->latest()->get(),
            'apiClient' => ApiClient::where('client_user_id', $user->id)->with('ipWhitelists', 'webhookSubscriptions')->first(),
            'shipments' => \App\Models\Shipment::where('client_user_id', $user->id)->latest()->limit(25)->get(),
            'accounts' => $user->accounts()->with('businessManager')->orderByDesc('is_default')->orderBy('account_name')->get(),
        ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        // shipments.client_user_id is nullOnDelete — deleting the
        // client wouldn't fail loudly, it would silently orphan every
        // shipment they've ever had (still there, just with no client
        // attached). Blocked outright rather than letting that happen
        // quietly; a client with shipment history isn't something
        // this action should be able to erase.
        if (\App\Models\Shipment::where('client_user_id', $user->id)->exists()) {
            return redirect()->route('clients.show', $user)->withErrors(['client' => "Can't remove {$user->name} — they have shipment history. Removing them would disconnect that history from any client record."]);
        }

        $user->delete();

        return redirect()->route('clients.index')->with('status', 'Client account removed.');
    }

    /**
     * Adds another operational Account under this Client (Lagos,
     * Abuja, E-commerce...) — a minimal starting point (name + type),
     * not the full profile form. Fill in address/contact/products/
     * billing/Business Manager afterward by switching to it
     * (setDefaultAccount) and using the same Edit/Tariff/Discount/etc.
     * flows already built for the Default Account.
     */
    public function storeAccount(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $data = $this->validated(Validator::make($request->all(), [
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:individual,organization',
        ]), $user, 'accounts');

        ClientAccount::create([
            'client_user_id' => $user->id,
            'account_name' => $data['account_name'],
            'account_number' => $this->resolveAccountNumber($request, null, null, auth()->user()),
            'is_default' => false,
            'account_type' => $data['account_type'],
            'created_by' => auth()->id(),
        ]);

        return $this->redirectToTab($user, 'accounts', "Account \"{$data['account_name']}\" created — switch to it below to configure its details, products, and billing.");
    }

    /**
     * Every tab besides "Accounts" itself (Overview, Tariff, Discount,
     * Department, User, Service, Managerial services) currently
     * operates on whichever Account is_default=true — this is how
     * staff choose which one that is. Products/billing/Business
     * Manager already configured on the account being switched TO stay
     * exactly as they were; nothing is copied or reset.
     */
    public function setDefaultAccount(User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        ClientAccount::where('client_user_id', $user->id)->update(['is_default' => false]);
        $account->update(['is_default' => true]);

        return $this->redirectToTab($user, 'accounts', "Now viewing \"{$account->account_name}\" — the tabs below reflect this account.");
    }

    public function destroyAccount(User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        if ($account->is_default) {
            return $this->redirectToTab($user, 'accounts', "Can't remove \"{$account->account_name}\" while it's in use — switch to a different account first.");
        }

        if ($user->accounts()->count() <= 1) {
            return $this->redirectToTab($user, 'accounts', 'A client must have at least one account.');
        }

        $account->delete();

        return $this->redirectToTab($user, 'accounts', "Account \"{$account->account_name}\" removed.");
    }

    /**
     * Unlike most other write actions on this page (which always
     * target whichever account is currently default), this one takes
     * an explicit $account — the Accounts tab lets staff edit any
     * account's contact/billing/invoicing details directly, without
     * switching to it first, since these are exactly the fields that
     * genuinely differ per account.
     */
    public function updateAccountBillingInfo(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        $validator = Validator::make($request->all(), [
            'use_default_contact' => 'sometimes|boolean',
            'contact_person_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'billing_address' => 'nullable|string|max:1000',
            'tin' => 'nullable|string|max:255',
            'is_vatable' => 'sometimes|boolean',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'is_pickup_chargeable' => 'sometimes|boolean',
            'pickup_charge' => 'required_if:is_pickup_chargeable,1|nullable|numeric|min:0',
            'is_onforwarding_chargeable' => 'sometimes|boolean',
            'onforwarding_charge' => 'required_if:is_onforwarding_chargeable,1|nullable|numeric|min:0',
            'maximum_delivery_attempts' => 'nullable|integer|min:1',
            'invoice_due_days' => 'nullable|integer|min:0',
        ]);
        $data = $this->validated($validator, $user, 'accounts', 'billingInfo' . $account->id);

        $useDefault = $account->is_default ? false : $request->boolean('use_default_contact');

        $account->update([
            'use_default_contact' => $useDefault,
            // Still saved even when linked to the default account's
            // info — so switching the checkbox back off later
            // restores whatever was last entered here instead of
            // reverting to blank.
            'contact_person_name' => $data['contact_person_name'] ?? null,
            'address' => $data['address'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'tin' => $data['tin'] ?? null,
            'is_vatable' => $request->boolean('is_vatable'),
            'vat_percentage' => $data['vat_percentage'] ?? null,
            'is_pickup_chargeable' => $request->boolean('is_pickup_chargeable'),
            'pickup_charge' => $request->boolean('is_pickup_chargeable') ? $data['pickup_charge'] : null,
            'is_onforwarding_chargeable' => $request->boolean('is_onforwarding_chargeable'),
            'onforwarding_charge' => $request->boolean('is_onforwarding_chargeable') ? $data['onforwarding_charge'] : null,
            'maximum_delivery_attempts' => $data['maximum_delivery_attempts'] ?? null,
            'invoice_due_days' => $data['invoice_due_days'] ?? null,
        ]);

        return $this->redirectToTab($user, 'accounts', "Billing & invoicing details updated for \"{$account->account_name}\".", $account);
    }

    /**
     * Manual entry (Company Settings -> allow_manual_account_number)
     * wins when it's turned on AND the requester actually supplied
     * one — validated for uniqueness here since the field itself
     * isn't part of the shared validateForm() rules (only relevant
     * when this setting is on). Otherwise falls through to the
     * normal generated number.
     */
    private function resolveAccountNumber(Request $request, ?State $state, ?\App\Models\Outlet $outlet, User $creator): string
    {
        if (\App\Models\Setting::current()->allow_manual_account_number && $request->filled('account_number')) {
            $manual = Validator::make($request->all(), [
                'account_number' => 'required|string|max:255|unique:client_accounts,account_number',
            ])->validate()['account_number'];

            return $manual;
        }

        return ClientAccount::generateAccountNumber($state, $outlet, $creator);
    }

    /**
     * Individual -> organization only, on purpose — see
     * client_accounts' note on why this doesn't go the other way in
     * the UI (an org that "downgrades" would lose its RC/TIN trail).
     * Requires the organization fields as part of the same request.
     * Same Account row — id, shipments, billing, Business Manager all
     * preserved automatically since nothing is recreated, only updated.
     */
    public function upgrade(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $account = $user->defaultAccount;
        abort_if(! $account || $account->account_type === 'organization', 404);

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'rc_number' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_role' => 'nullable|string|max:255',
        ]);
        $data = $this->validated($validator, $user, 'overview');

        $account->update([...$data, 'account_type' => 'organization']);

        return $this->redirectToTab($user, 'overview', "{$user->name} upgraded to an organization account.");
    }

    public function storeDiscount(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);
        $data = $this->validated($validator, $user, 'billing');

        ClientServiceDiscount::updateOrCreate(
            ['client_account_id' => $account->id, 'service_type_id' => $data['service_type_id']],
            ['client_user_id' => $user->id, 'discount_percentage' => $data['discount_percentage']]
        );

        return $this->redirectToTab($user, 'billing', 'Discount save, $account).');
    }

    public function destroyDiscount(User $user, ClientServiceDiscount $discount): RedirectResponse
    {
        abort_unless($discount->clientAccount?->client_user_id === $user->id, 404);

        $discount->delete();

        return $this->redirectToTab($user, 'billing', 'Discount removed — this service type now bills standard for this client.', $discount->clientAccount);
    }

    public function storeSpecialTariff(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

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

        $validator->after(function ($validator) use ($request, $account) {
            $this->rejectIfSpecialTariffOverlapping(
                $validator,
                'max_weight_limit',
                $account->id,
                (int) $request->input('service_type_id'),
                (float) $request->input('min_weight'),
                (float) $request->input('max_weight_limit')
            );
        });

        $data = $this->validated($validator, $user, 'billing', 'standardTariff');

        $tariff = ClientSpecialTariff::create([
            'client_account_id' => $account->id,
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

        return $this->redirectToTab($user, 'billing', 'Special, $account)rate added.');
    }

    public function destroySpecialTariff(User $user, ClientSpecialTariff $tariff): RedirectResponse
    {
        abort_unless($tariff->clientAccount?->client_user_id === $user->id, 404);

        $tariff->delete();

        return $this->redirectToTab($user, 'billing', 'Special rate removed — this weight band now bills standard for this client.', $tariff->clientAccount);
    }

    /**
     * Reconciles zone_prices to exactly what was submitted — any
     * existing zone price whose zone isn't in this submission gets
     * removed, so taking a zone row out of the edit form actually
     * removes that zone's pricing rather than leaving it stale.
     */
    public function updateSpecialTariff(Request $request, User $user, ClientSpecialTariff $tariff): RedirectResponse
    {
        abort_unless($tariff->clientAccount?->client_user_id === $user->id, 404);

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

        $validator->after(function ($validator) use ($request, $tariff) {
            $this->rejectIfSpecialTariffOverlapping(
                $validator,
                'max_weight_limit',
                $tariff->client_account_id,
                (int) $request->input('service_type_id'),
                (float) $request->input('min_weight'),
                (float) $request->input('max_weight_limit'),
                $tariff
            );
        });

        $data = $this->validated($validator, $user, 'billing', 'standardTariff' . $tariff->id);

        $tariff->update([
            'service_type_id' => $data['service_type_id'],
            'min_weight' => $data['min_weight'],
            'max_weight' => $data['max_weight'],
            'max_weight_limit' => $data['max_weight_limit'],
            'additional_weight' => $data['additional_weight'],
        ]);

        $submittedZoneIds = collect($data['zone_prices'])->pluck('zone_id');

        foreach ($data['zone_prices'] as $zonePrice) {
            ClientSpecialTariffZonePrice::updateOrCreate(
                ['client_special_tariff_id' => $tariff->id, 'zone_id' => $zonePrice['zone_id']],
                [
                    'charge' => $zonePrice['charge'],
                    'additional_charge' => $zonePrice['additional_charge'] ?? 0,
                    'transit_days' => $zonePrice['transit_days'] ?? null,
                ]
            );
        }

        $tariff->zonePrices()->whereNotIn('zone_id', $submittedZoneIds)->delete();

        return $this->redirectToTab($user, 'billing', 'Special rate updated.', $tariff->clientAccount);
    }

    public function storeOriginDestinationTariff(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'origin_type' => 'required|in:state,country',
            'origin_state_id' => 'required_if:origin_type,state|nullable|exists:states,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'origin_country_id' => 'required_if:origin_type,country|nullable|exists:countries,id',
            'destination_type' => 'required|in:state,country',
            'destination_state_id' => 'required_if:destination_type,state|nullable|exists:states,id',
            'destination_city_id' => 'nullable|exists:cities,id',
            'destination_country_id' => 'required_if:destination_type,country|nullable|exists:countries,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|min:0',
            'max_weight_limit' => 'required|numeric|gt:min_weight',
            'base_charge' => 'required|numeric|min:0',
            'additional_weight' => 'required|numeric|min:0.01',
            'additional_charge' => 'required|numeric|min:0',
            'transit_days' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request, $account) {
            $this->rejectIfOriginDestinationTariffOverlapping(
                $validator,
                'max_weight_limit',
                $account->id,
                $this->normalizeRouteFields($request->all()),
                (float) $request->input('min_weight'),
                (float) $request->input('max_weight_limit')
            );
        });

        $data = $this->validated($validator, $user, 'billing', 'odTariff');

        // Same "state XOR country" clearing as the company-level form —
        // a route is one or the other, never both.
        if ($data['origin_type'] === 'country') {
            $data['origin_state_id'] = null;
            $data['origin_city_id'] = null;
        } else {
            $data['origin_country_id'] = null;
        }
        if ($data['destination_type'] === 'country') {
            $data['destination_state_id'] = null;
            $data['destination_city_id'] = null;
        } else {
            $data['destination_country_id'] = null;
        }

        \App\Models\ClientOriginDestinationTariff::create([
            'client_account_id' => $account->id,
            'client_user_id' => $user->id,
            'service_type_id' => $data['service_type_id'],
            'origin_state_id' => $data['origin_state_id'],
            'origin_city_id' => $data['origin_city_id'] ?? null,
            'origin_country_id' => $data['origin_country_id'],
            'destination_state_id' => $data['destination_state_id'],
            'destination_city_id' => $data['destination_city_id'] ?? null,
            'destination_country_id' => $data['destination_country_id'],
            'min_weight' => $data['min_weight'],
            'max_weight' => $data['max_weight'],
            'max_weight_limit' => $data['max_weight_limit'],
            'base_charge' => $data['base_charge'],
            'additional_weight' => $data['additional_weight'],
            'additional_charge' => $data['additional_charge'],
            'transit_days' => $data['transit_days'] ?? null,
            'is_active' => true,
        ]);

        return $this->redirectToTab($user, 'billing', 'Special Origin-to-D, $account)stination rate added.');
    }

    public function destroyOriginDestinationTariff(User $user, \App\Models\ClientOriginDestinationTariff $tariff): RedirectResponse
    {
        abort_unless($tariff->clientAccount?->client_user_id === $user->id, 404);

        $tariff->delete();

        return $this->redirectToTab($user, 'billing', 'Special rate removed — this route now bills at the company rate for this client.', $tariff->clientAccount);
    }

    public function updateOriginDestinationTariff(Request $request, User $user, \App\Models\ClientOriginDestinationTariff $tariff): RedirectResponse
    {
        abort_unless($tariff->clientAccount?->client_user_id === $user->id, 404);

        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'origin_type' => 'required|in:state,country',
            'origin_state_id' => 'required_if:origin_type,state|nullable|exists:states,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'origin_country_id' => 'required_if:origin_type,country|nullable|exists:countries,id',
            'destination_type' => 'required|in:state,country',
            'destination_state_id' => 'required_if:destination_type,state|nullable|exists:states,id',
            'destination_city_id' => 'nullable|exists:cities,id',
            'destination_country_id' => 'required_if:destination_type,country|nullable|exists:countries,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|min:0',
            'max_weight_limit' => 'required|numeric|gt:min_weight',
            'base_charge' => 'required|numeric|min:0',
            'additional_weight' => 'required|numeric|min:0.01',
            'additional_charge' => 'required|numeric|min:0',
            'transit_days' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request, $tariff) {
            $this->rejectIfOriginDestinationTariffOverlapping(
                $validator,
                'max_weight_limit',
                $tariff->client_account_id,
                $this->normalizeRouteFields($request->all()),
                (float) $request->input('min_weight'),
                (float) $request->input('max_weight_limit'),
                $tariff
            );
        });

        $data = $this->validated($validator, $user, 'billing', 'odTariff' . $tariff->id);

        if ($data['origin_type'] === 'country') {
            $data['origin_state_id'] = null;
            $data['origin_city_id'] = null;
        } else {
            $data['origin_country_id'] = null;
        }
        if ($data['destination_type'] === 'country') {
            $data['destination_state_id'] = null;
            $data['destination_city_id'] = null;
        } else {
            $data['destination_country_id'] = null;
        }

        $tariff->update([
            'service_type_id' => $data['service_type_id'],
            'origin_state_id' => $data['origin_state_id'],
            'origin_city_id' => $data['origin_city_id'] ?? null,
            'origin_country_id' => $data['origin_country_id'],
            'destination_state_id' => $data['destination_state_id'],
            'destination_city_id' => $data['destination_city_id'] ?? null,
            'destination_country_id' => $data['destination_country_id'],
            'min_weight' => $data['min_weight'],
            'max_weight' => $data['max_weight'],
            'max_weight_limit' => $data['max_weight_limit'],
            'base_charge' => $data['base_charge'],
            'additional_weight' => $data['additional_weight'],
            'additional_charge' => $data['additional_charge'],
            'transit_days' => $data['transit_days'] ?? null,
        ]);

        return $this->redirectToTab($user, 'billing', 'Special Origin-to-Destination rate updated.', $tariff->clientAccount);
    }

    public function storeFleetTariff(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'origin_type' => 'required|in:state,country',
            'origin_state_id' => 'required_if:origin_type,state|nullable|exists:states,id',
            'origin_country_id' => 'required_if:origin_type,country|nullable|exists:countries,id',
            'destination_type' => 'required|in:state,country',
            'destination_state_id' => 'required_if:destination_type,state|nullable|exists:states,id',
            'destination_country_id' => 'required_if:destination_type,country|nullable|exists:countries,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|min:0',
            'max_weight_limit' => 'required|numeric|gt:min_weight',
            'base_charge' => 'required|numeric|min:0',
            'additional_weight' => 'required|numeric|min:0.01',
            'additional_charge' => 'required|numeric|min:0',
            'fuel_surcharge_percentage' => 'required|numeric|min:0|max:100',
            'empty_return_charge_type' => 'required|in:flat,percentage',
            'empty_return_charge_value' => 'required|numeric|min:0',
            'transit_days' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request, $account) {
            $this->rejectIfFleetTariffOverlapping(
                $validator,
                'max_weight_limit',
                $account->id,
                $this->normalizeRouteFields($request->all()),
                (float) $request->input('min_weight'),
                (float) $request->input('max_weight_limit')
            );
        });

        $data = $this->validated($validator, $user, 'billing', 'fleetTariff');

        // Same "state XOR country" clearing as Origin-to-Destination —
        // a route is one or the other, never both. Fleet only reaches
        // 'country' at all when the form's Route toggle was set to
        // International (domestic forces the type select back to
        // 'state' client-side), but this server-side clearing is what
        // actually guarantees the stored row never carries both.
        if ($data['origin_type'] === 'country') {
            $data['origin_state_id'] = null;
        } else {
            $data['origin_country_id'] = null;
        }
        if ($data['destination_type'] === 'country') {
            $data['destination_state_id'] = null;
        } else {
            $data['destination_country_id'] = null;
        }

        \App\Models\ClientFleetBillingTariff::create([
            'client_account_id' => $account->id,
            'client_user_id' => $user->id,
            'service_type_id' => $data['service_type_id'],
            'vehicle_type_id' => $data['vehicle_type_id'],
            'origin_state_id' => $data['origin_state_id'],
            'origin_country_id' => $data['origin_country_id'],
            'destination_state_id' => $data['destination_state_id'],
            'destination_country_id' => $data['destination_country_id'],
            'min_weight' => $data['min_weight'],
            'max_weight' => $data['max_weight'],
            'max_weight_limit' => $data['max_weight_limit'],
            'base_charge' => $data['base_charge'],
            'additional_weight' => $data['additional_weight'],
            'additional_charge' => $data['additional_charge'],
            'fuel_surcharge_percentage' => $data['fuel_surcharge_percentage'],
            'empty_return_charge_type' => $data['empty_return_charge_type'],
            'empty_return_charge_value' => $data['empty_return_charge_value'],
            'transit_days' => $data['transit_days'] ?? null,
            'is_active' => true,
        ]);

        return $this->redirectToTab($user, 'bill, $account)ng', 'Special Fleet rate added.');
    }

    public function destroyFleetTariff(User $user, \App\Models\ClientFleetBillingTariff $tariff): RedirectResponse
    {
        abort_unless($tariff->clientAccount?->client_user_id === $user->id, 404);

        $tariff->delete();

        return $this->redirectToTab($user, 'billing', 'Special rate removed — this vehicle type/route now bills at the company rate for this client.', $tariff->clientAccount);
    }

    public function updateFleetTariff(Request $request, User $user, \App\Models\ClientFleetBillingTariff $tariff): RedirectResponse
    {
        abort_unless($tariff->clientAccount?->client_user_id === $user->id, 404);

        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'origin_type' => 'required|in:state,country',
            'origin_state_id' => 'required_if:origin_type,state|nullable|exists:states,id',
            'origin_country_id' => 'required_if:origin_type,country|nullable|exists:countries,id',
            'destination_type' => 'required|in:state,country',
            'destination_state_id' => 'required_if:destination_type,state|nullable|exists:states,id',
            'destination_country_id' => 'required_if:destination_type,country|nullable|exists:countries,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|min:0',
            'max_weight_limit' => 'required|numeric|gt:min_weight',
            'base_charge' => 'required|numeric|min:0',
            'additional_weight' => 'required|numeric|min:0.01',
            'additional_charge' => 'required|numeric|min:0',
            'fuel_surcharge_percentage' => 'required|numeric|min:0|max:100',
            'empty_return_charge_type' => 'required|in:flat,percentage',
            'empty_return_charge_value' => 'required|numeric|min:0',
            'transit_days' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request, $tariff) {
            $this->rejectIfFleetTariffOverlapping(
                $validator,
                'max_weight_limit',
                $tariff->client_account_id,
                $this->normalizeRouteFields($request->all()),
                (float) $request->input('min_weight'),
                (float) $request->input('max_weight_limit'),
                $tariff
            );
        });

        $data = $this->validated($validator, $user, 'billing', 'fleetTariff' . $tariff->id);

        if ($data['origin_type'] === 'country') {
            $data['origin_state_id'] = null;
        } else {
            $data['origin_country_id'] = null;
        }
        if ($data['destination_type'] === 'country') {
            $data['destination_state_id'] = null;
        } else {
            $data['destination_country_id'] = null;
        }

        $tariff->update([
            'service_type_id' => $data['service_type_id'],
            'vehicle_type_id' => $data['vehicle_type_id'],
            'origin_state_id' => $data['origin_state_id'],
            'origin_country_id' => $data['origin_country_id'],
            'destination_state_id' => $data['destination_state_id'],
            'destination_country_id' => $data['destination_country_id'],
            'min_weight' => $data['min_weight'],
            'max_weight' => $data['max_weight'],
            'max_weight_limit' => $data['max_weight_limit'],
            'base_charge' => $data['base_charge'],
            'additional_weight' => $data['additional_weight'],
            'additional_charge' => $data['additional_charge'],
            'fuel_surcharge_percentage' => $data['fuel_surcharge_percentage'],
            'empty_return_charge_type' => $data['empty_return_charge_type'],
            'empty_return_charge_value' => $data['empty_return_charge_value'],
            'transit_days' => $data['transit_days'] ?? null,
        ]);

        return $this->redirectToTab($user, 'billing', 'Special Fleet rate updated.', $tariff->clientAccount);
    }

    /**
     * Same combined format as StandardBillingController::importAll()
     * and its own note on why — rows sharing the same (service type,
     * weight range) build up one special tariff's several zone prices.
     * Scoped to this account throughout: an exact-match tariff for
     * THIS account is reused (re-importing an amended export updates
     * rather than duplicates); a genuinely new weight range never
     * checks for overlap against other accounts' tariffs, only this
     * one's.
     */
    public function importSpecialTariff(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $tariffCache = [];
        $tariffsCreated = 0;
        $pricesSaved = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $serviceType = ServiceType::where('code', strtoupper(trim($row['service_type_code'] ?? '')))->first();
            $minWeight = $row['min_weight'] ?? null;
            $maxWeightLimit = $row['max_weight_limit'] ?? null;

            if (! $serviceType || ! is_numeric($minWeight) || ! is_numeric($maxWeightLimit)) {
                $skipped++;
                continue;
            }

            $cacheKey = "{$serviceType->id}:{$minWeight}:{$maxWeightLimit}";

            if (! isset($tariffCache[$cacheKey])) {
                $tariff = ClientSpecialTariff::where('client_account_id', $account->id)
                    ->where('service_type_id', $serviceType->id)
                    ->where('min_weight', $minWeight)->where('max_weight_limit', $maxWeightLimit)->first();

                if (! $tariff) {
                    $overlaps = ClientSpecialTariff::where('client_account_id', $account->id)
                        ->where('service_type_id', $serviceType->id)
                        ->where('is_active', true)
                        ->get(['min_weight', 'max_weight_limit'])
                        ->contains(fn ($t) => $this->rangesOverlap((float) $minWeight, (float) $maxWeightLimit, (float) $t->min_weight, (float) $t->max_weight_limit));

                    if ($overlaps) {
                        $skipped++;
                        continue;
                    }

                    $tariff = ClientSpecialTariff::create([
                        'client_account_id' => $account->id,
                        'client_user_id' => $user->id,
                        'service_type_id' => $serviceType->id,
                        'min_weight' => $minWeight,
                        'max_weight' => is_numeric($row['max_weight'] ?? null) ? $row['max_weight'] : $minWeight,
                        'max_weight_limit' => $maxWeightLimit,
                        'additional_weight' => is_numeric($row['additional_weight'] ?? null) ? $row['additional_weight'] : 1,
                        'is_active' => true,
                    ]);
                    $tariffsCreated++;
                }

                $tariffCache[$cacheKey] = $tariff;
            }

            if (! empty($row['zone_code'])) {
                $zone = Zone::where('code', strtoupper(trim($row['zone_code'])))->first();

                if ($zone && is_numeric($row['charge'] ?? null)) {
                    ClientSpecialTariffZonePrice::updateOrCreate(
                        ['client_special_tariff_id' => $tariffCache[$cacheKey]->id, 'zone_id' => $zone->id],
                        [
                            'charge' => $row['charge'],
                            'additional_charge' => is_numeric($row['additional_charge'] ?? null) ? $row['additional_charge'] : 0,
                            'transit_days' => ($row['transit_days'] ?? '') !== '' ? (int) $row['transit_days'] : null,
                        ]
                    );
                    $pricesSaved++;
                }
            }
        }

        return $this->redirectToTab($user, 'billing', "Imported: {$tariffsCreated} special rates created, {$pricesSaved} zone prices saved" . ($skipped ? ", {$skipped} rows skipped (unknown service type, ove, $account)lapping range, or missing weight)." : '.'));
    }

    public function importOriginDestinationTariff(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $serviceType = ServiceType::where('code', strtoupper(trim($row['product_code'] ?? '')))->first();
            $minWeight = $row['base_weight'] ?? null;
            $maxWeightLimit = $row['max_weight_limit'] ?? null;

            $originCountry = ! empty($row['origin_country_code'])
                ? Country::where('code', strtoupper(trim($row['origin_country_code'])))->first()
                : null;
            $originState = ! $originCountry && ! empty($row['origin_state_code'])
                ? State::where('short_code', strtoupper(trim($row['origin_state_code'])))->first()
                : null;

            $destinationCountry = ! empty($row['destination_country_code'])
                ? Country::where('code', strtoupper(trim($row['destination_country_code'])))->first()
                : null;
            $destinationState = ! $destinationCountry && ! empty($row['destination_state_code'])
                ? State::where('short_code', strtoupper(trim($row['destination_state_code'])))->first()
                : null;

            $originResolved = $originCountry || $originState;
            $destinationResolved = $destinationCountry || $destinationState;

            if (! $originResolved || ! $destinationResolved || ! $serviceType || ! is_numeric($minWeight) || ! is_numeric($maxWeightLimit)) {
                $skipped++;
                continue;
            }

            $originCity = ($originState && ! empty($row['origin_city_code']))
                ? City::where('state_id', $originState->id)->where('short_code', strtoupper(trim($row['origin_city_code'])))->first()
                : null;
            $destinationCity = ($destinationState && ! empty($row['destination_city_code']))
                ? City::where('state_id', $destinationState->id)->where('short_code', strtoupper(trim($row['destination_city_code'])))->first()
                : null;

            // Only rejects a DIFFERENT range that overlaps this one —
            // an exact match on every route field + weight band is a
            // legitimate re-import (updateOrCreate below updates it),
            // not a conflict.
            $overlaps = \App\Models\ClientOriginDestinationTariff::where('client_account_id', $account->id)
                ->where('service_type_id', $serviceType->id)
                ->where('origin_state_id', $originState?->id)
                ->where('origin_city_id', $originCity?->id)
                ->where('origin_country_id', $originCountry?->id)
                ->where('destination_state_id', $destinationState?->id)
                ->where('destination_city_id', $destinationCity?->id)
                ->where('destination_country_id', $destinationCountry?->id)
                ->where('is_active', true)
                ->where(fn ($q) => $q->where('min_weight', '!=', $minWeight)->orWhere('max_weight_limit', '!=', $maxWeightLimit))
                ->get(['min_weight', 'max_weight_limit'])
                ->contains(fn ($t) => $this->rangesOverlap((float) $minWeight, (float) $maxWeightLimit, (float) $t->min_weight, (float) $t->max_weight_limit));

            if ($overlaps) {
                $skipped++;
                continue;
            }

            \App\Models\ClientOriginDestinationTariff::updateOrCreate(
                [
                    'client_account_id' => $account->id,
                    'service_type_id' => $serviceType->id,
                    'origin_state_id' => $originState?->id,
                    'origin_city_id' => $originCity?->id,
                    'origin_country_id' => $originCountry?->id,
                    'destination_state_id' => $destinationState?->id,
                    'destination_city_id' => $destinationCity?->id,
                    'destination_country_id' => $destinationCountry?->id,
                    'min_weight' => $minWeight,
                    'max_weight_limit' => $maxWeightLimit,
                ],
                [
                    'client_user_id' => $user->id,
                    'max_weight' => is_numeric($row['max_weight'] ?? null) ? $row['max_weight'] : $minWeight,
                    'base_charge' => is_numeric($row['base_charge'] ?? null) ? $row['base_charge'] : 0,
                    'additional_weight' => is_numeric($row['additional_weight'] ?? null) ? $row['additional_weight'] : 1,
                    'additional_charge' => is_numeric($row['additional_charge'] ?? null) ? $row['additional_charge'] : 0,
                    'transit_days' => is_numeric($row['transit_days'] ?? null) ? $row['transit_days'] : null,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return $this->redirectToTab($user, 'billing', "Imported {$count} special Origin-to-Destination rates" . ($skipped ? ", skipped {$skipped} (unknown state/country/product, $account)code, overlapping range, or missing weight)." : '.'));
    }

    public function importFleetTariff(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $vehicleType = \App\Models\VehicleType::where('code', strtoupper(trim($row['vehicle_type_code'] ?? '')))->first();
            $serviceType = ServiceType::where('code', strtoupper(trim($row['product_code'] ?? '')))->first();
            $minWeight = $row['base_weight'] ?? null;
            $maxWeightLimit = $row['max_weight_limit'] ?? null;

            $originCountry = ! empty($row['origin_country_code'])
                ? Country::where('code', strtoupper(trim($row['origin_country_code'])))->first()
                : null;
            $originState = ! $originCountry && ! empty($row['origin_state_code'])
                ? State::where('short_code', strtoupper(trim($row['origin_state_code'])))->first()
                : null;

            $destinationCountry = ! empty($row['destination_country_code'])
                ? Country::where('code', strtoupper(trim($row['destination_country_code'])))->first()
                : null;
            $destinationState = ! $destinationCountry && ! empty($row['destination_state_code'])
                ? State::where('short_code', strtoupper(trim($row['destination_state_code'])))->first()
                : null;

            $originResolved = $originCountry || $originState;
            $destinationResolved = $destinationCountry || $destinationState;

            if (! $vehicleType || ! $serviceType || ! $originResolved || ! $destinationResolved || ! is_numeric($minWeight) || ! is_numeric($maxWeightLimit)) {
                $skipped++;
                continue;
            }

            $originCity = ($originState && ! empty($row['origin_city_code']))
                ? City::where('state_id', $originState->id)->where('short_code', strtoupper(trim($row['origin_city_code'])))->first()
                : null;
            $destinationCity = ($destinationState && ! empty($row['destination_city_code']))
                ? City::where('state_id', $destinationState->id)->where('short_code', strtoupper(trim($row['destination_city_code'])))->first()
                : null;

            // Only rejects a DIFFERENT range that overlaps this one —
            // an exact match on vehicle type + every route field +
            // weight band is a legitimate re-import, not a conflict.
            $overlaps = \App\Models\ClientFleetBillingTariff::where('client_account_id', $account->id)
                ->where('service_type_id', $serviceType->id)
                ->where('vehicle_type_id', $vehicleType->id)
                ->where('origin_state_id', $originState?->id)
                ->where('origin_city_id', $originCity?->id)
                ->where('origin_country_id', $originCountry?->id)
                ->where('destination_state_id', $destinationState?->id)
                ->where('destination_city_id', $destinationCity?->id)
                ->where('destination_country_id', $destinationCountry?->id)
                ->where('is_active', true)
                ->where(fn ($q) => $q->where('min_weight', '!=', $minWeight)->orWhere('max_weight_limit', '!=', $maxWeightLimit))
                ->get(['min_weight', 'max_weight_limit'])
                ->contains(fn ($t) => $this->rangesOverlap((float) $minWeight, (float) $maxWeightLimit, (float) $t->min_weight, (float) $t->max_weight_limit));

            if ($overlaps) {
                $skipped++;
                continue;
            }

            \App\Models\ClientFleetBillingTariff::updateOrCreate(
                [
                    'client_account_id' => $account->id,
                    'service_type_id' => $serviceType->id,
                    'vehicle_type_id' => $vehicleType->id,
                    'origin_state_id' => $originState?->id,
                    'origin_city_id' => $originCity?->id,
                    'origin_country_id' => $originCountry?->id,
                    'destination_state_id' => $destinationState?->id,
                    'destination_city_id' => $destinationCity?->id,
                    'destination_country_id' => $destinationCountry?->id,
                    'min_weight' => $minWeight,
                    'max_weight_limit' => $maxWeightLimit,
                ],
                [
                    'client_user_id' => $user->id,
                    'max_weight' => is_numeric($row['max_weight'] ?? null) ? $row['max_weight'] : $minWeight,
                    'base_charge' => is_numeric($row['weight_base_charge'] ?? null) ? $row['weight_base_charge'] : 0,
                    'additional_weight' => is_numeric($row['additional_weight'] ?? null) ? $row['additional_weight'] : 1,
                    'additional_charge' => is_numeric($row['additional_charge'] ?? null) ? $row['additional_charge'] : 0,
                    'fuel_surcharge_percentage' => is_numeric($row['fuel_surcharge_percentage'] ?? null) ? $row['fuel_surcharge_percentage'] : 0,
                    'empty_return_charge_type' => in_array($row['empty_return_charge_type'] ?? null, ['flat', 'percentage']) ? $row['empty_return_charge_type'] : 'flat',
                    'empty_return_charge_value' => is_numeric($row['empty_return_charge_value'] ?? null) ? $row['empty_return_charge_value'] : 0,
                    'transit_days' => is_numeric($row['transit_days'] ?? null) ? $row['transit_days'] : null,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return $this->redirectToTab($user, 'billing', "Imported {$count} special Fleet rates" . ($skipped ? ", skipped {$skipped} (unknown vehicle/state/count, $account)y/product code, overlapping range, or missing weight)." : '.'));
    }

    /**
     * Three blank templates (headers only, one sample row) matching
     * exactly what each import above expects — so staff never have to
     * guess column names or order by reverse-engineering the importer.
     */
    public function downloadTariffTemplate(string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return match ($type) {
            'standard' => $this->csv->download('special-rate-standard-template.csv',
                ['service_type_code', 'min_weight', 'max_weight', 'max_weight_limit', 'additional_weight', 'zone_code', 'charge', 'additional_charge', 'transit_days'],
                [['EXP', 0, 2, 2, 1, 'Z1', 1500, 200, 1]]
            ),
            'od' => $this->csv->download('special-rate-od-template.csv',
                ['product_code', 'origin_state_code', 'origin_city_code', 'origin_country_code', 'destination_state_code', 'destination_city_code', 'destination_country_code', 'base_weight', 'max_weight', 'max_weight_limit', 'additional_weight', 'base_charge', 'additional_charge', 'transit_days'],
                [['ISF', 'LA', '', '', 'FC', '', '', 0, 10, 10, 1, 5500, 400, 2]]
            ),
            'fleet' => $this->csv->download('special-rate-fleet-template.csv',
                ['product_code', 'vehicle_type_code', 'origin_state_code', 'origin_city_code', 'origin_country_code', 'destination_state_code', 'destination_city_code', 'destination_country_code', 'base_weight', 'max_weight', 'max_weight_limit', 'additional_weight', 'weight_base_charge', 'additional_charge', 'fuel_surcharge_percentage', 'empty_return_charge_type', 'empty_return_charge_value', 'transit_days'],
                [['FLT', 'VAN', 'LA', '', '', 'FC', '', '', 0, 500, 500, 1, 15000, 80, 0, 'flat', 0, 2]]
            ),
            default => abort(404),
        };
    }

    /**
     * Toggles which company-enabled billing models this account can
     * actually use — "this client never uses Fleet at all." Checkboxes
     * that are UNCHECKED (i.e. the model should be disabled) submit
     * nothing, so the array of DISABLED models has to be built from
     * which ones weren't checked, not read directly off the request.
     */
    public function updateDisabledBillingModels(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        $enabled = $request->input('enabled_billing_models', []);
        $allModels = array_keys(\App\Models\Setting::current()->supportedBillingModels());
        $disabled = array_values(array_diff($allModels, $enabled));

        $account->update(['disabled_billing_models' => $disabled]);

        return $this->redirectToTab($user, 'billing', 'Billing model availability updated.', $account);
    }

    /**
     * Switches one billing model between Standard and Special for a
     * SPECIFIC account — takes the account explicitly (route-bound),
     * not via requireDefaultAccount(), so this doesn't require
     * switching an account to "default" first to configure it. Special
     * genuinely replaces Standard's discount for this model
     * (ShipmentPricingService::priceShipment() checks
     * ClientAccount::isSpecialFor()) — this is the one action that
     * flips that switch.
     */
    public function updateBillingModelMode(Request $request, User $user, \App\Models\ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        $data = $this->validated(Validator::make($request->all(), [
            'billing_model' => 'required|string|in:' . implode(',', array_keys(\App\Models\Setting::BILLING_MODELS)),
            'mode' => 'required|in:standard,special',
        ]), $user, 'billing');

        $special = collect($account->special_billing_models ?? [])->reject(fn ($m) => $m === $data['billing_model'])->values()->all();

        if ($data['mode'] === 'special') {
            $special[] = $data['billing_model'];
        }

        $account->update(['special_billing_models' => $special]);

        return $this->redirectToTab($user, 'billing', 'Billing mode updated.', $account);
    }

    /**
     * Separate from updateBillingModelMode() on purpose — this only
     * ever matters once a model is already in Special mode, and is a
     * genuinely different decision (whether a coverage gap blocks or
     * falls back), not a variant of the mode switch itself.
     */
    public function updateBillingModelFallback(Request $request, User $user, \App\Models\ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        $data = $this->validated(Validator::make($request->all(), [
            'billing_model' => 'required|string|in:' . implode(',', array_keys(\App\Models\Setting::BILLING_MODELS)),
            'allow_fallback' => 'sometimes|boolean',
        ]), $user, 'billing');

        $fallback = collect($account->special_fallback_models ?? [])->reject(fn ($m) => $m === $data['billing_model'])->values()->all();

        if ($request->boolean('allow_fallback')) {
            $fallback[] = $data['billing_model'];
        }

        $account->update(['special_fallback_models' => $fallback]);

        return $this->redirectToTab($user, 'billing', 'Fallback setting updated.', $account);
    }

    // ---------------------------------------------------------------
    // Department
    // ---------------------------------------------------------------

    public function storeDepartment(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);
        abort_unless($account->isOrganization(), 404);

        $data = $this->validated(Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]), $user, 'department');

        Department::create(['client_user_id' => $user->id, 'client_account_id' => $account->id, 'name' => $data['name']]);

        return $this->redirectToTab($user, 'department', 'Department added.', $account);
    }

    public function destroyDepartment(User $user, Department $department): RedirectResponse
    {
        // Any of the client's accounts, not just Default — a
        // department belongs to whichever account it was created
        // under, and that's no longer necessarily the Default one.
        abort_unless($department->clientAccount?->client_user_id === $user->id, 404);

        $department->delete();

        return $this->redirectToTab($user, 'department', 'Department removed.', $department->clientAccount);
    }

    // ---------------------------------------------------------------
    // Sub-users (organization only) — real, separate logins, scoped
    // to the account they work under
    // ---------------------------------------------------------------

    public function storeSubUser(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);
        abort_unless($account->isOrganization(), 404);

        $data = $this->validated(Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:30',
            'password' => 'required|string|min:8',
            'department_id' => 'nullable|exists:departments,id',
        ]), $user, 'users');

        // A department picked here must actually belong to THIS
        // account — exists:departments,id alone can't enforce that.
        if (! empty($data['department_id'])) {
            abort_unless(Department::where('id', $data['department_id'])->where('client_account_id', $account->id)->exists(), 422);
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
            'client_account_id' => $account->id,
            'department_id' => $data['department_id'] ?? null,
        ]);

        return $this->redirectToTab($user, 'users', "{$subUser->name} added as a user under {$user->name}.", $account);
    }

    public function destroySubUser(User $user, User $subUser): RedirectResponse
    {
        // Any of the client's accounts, not just Default — same
        // reasoning as destroyDepartment().
        $subUserAccountId = $subUser->clientProfile?->client_account_id;
        abort_unless($subUserAccountId && \App\Models\ClientAccount::where('id', $subUserAccountId)->where('client_user_id', $user->id)->exists(), 404);

        $subUser->delete();

        return $this->redirectToTab($user, 'users', 'User removed.', \App\Models\ClientAccount::find($subUserAccountId));
    }

    // ---------------------------------------------------------------
    // Service subscriptions — access, not pricing
    // ---------------------------------------------------------------

    public function storeServiceSubscription(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        $data = $this->validated(Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'is_active' => 'sometimes|boolean',
        ]), $user, 'billing');

        ClientServiceSubscription::updateOrCreate(
            ['client_account_id' => $account->id, 'service_type_id' => $data['service_type_id']],
            ['client_user_id' => $user->id, 'is_active' => $request->boolean('is_active')]
        );

        return $this->redirectToTab($user, 'billing', 'Service access updated.', $account);
    }

    // ---------------------------------------------------------------
    // Documents — client-level, not per-account (a signed business
    // agreement belongs to the company, not one operational branch)
    // ---------------------------------------------------------------

    public function storeDocument(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $data = $this->validated(Validator::make($request->all(), [
            'document_type' => 'required|in:' . implode(',', array_keys(ClientDocument::DOCUMENT_TYPES)),
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]), $user, 'document');

        $file = $request->file('file');
        $path = $file->store('client-documents/' . $user->id, 'public');

        ClientDocument::create([
            'client_user_id' => $user->id,
            'document_type' => $data['document_type'],
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'uploaded_by' => auth()->id(),
        ]);

        return $this->redirectToTab($user, 'document', 'Document uploaded.');
    }

    public function destroyDocument(User $user, ClientDocument $document): RedirectResponse
    {
        abort_unless($document->client_user_id === $user->id, 404);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return $this->redirectToTab($user, 'document', 'Document removed.');
    }

    // ---------------------------------------------------------------
    // Security — API access, client-level (reuses the same
    // ApiClient/IpWhitelist/WebhookSubscription system built for
    // external integration partners)
    // ---------------------------------------------------------------

    public function generateApiAccess(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'client', 404);

        $result = ApiClient::generateFor($user->id, $user->defaultAccount?->company_name ?: $user->name);

        // The plaintext secret only ever exists in this one response -
        // flashed to session for a single display, never persisted or
        // logged anywhere.
        return $this->redirectToTab($user, 'security', 'API access generated — copy the secret now, it will not be shown again.')
            ->with('plaintext_api_secret', $result['plaintext_secret']);
    }

    public function updateApiSettings(Request $request, User $user): RedirectResponse
    {
        $apiClient = ApiClient::where('client_user_id', $user->id)->firstOrFail();

        $data = $this->validated(Validator::make($request->all(), [
            'api_response_format' => 'required|in:url,base64',
            'ip_whitelist_enabled' => 'sometimes|boolean',
            'rate_limit_per_minute' => 'required|integer|min:1|max:6000',
        ]), $user, 'security');

        $apiClient->update([
            'api_response_format' => $data['api_response_format'],
            'ip_whitelist_enabled' => $request->boolean('ip_whitelist_enabled'),
            'rate_limit_per_minute' => $data['rate_limit_per_minute'],
        ]);

        return $this->redirectToTab($user, 'security', 'API settings updated.');
    }

    public function storeIpWhitelist(Request $request, User $user): RedirectResponse
    {
        $apiClient = ApiClient::where('client_user_id', $user->id)->firstOrFail();

        $data = $this->validated(Validator::make($request->all(), [
            'ip_or_cidr' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
        ]), $user, 'security');

        IpWhitelist::create([
            'api_client_id' => $apiClient->id,
            'ip_or_cidr' => $data['ip_or_cidr'],
            'label' => $data['label'] ?? null,
            'added_at' => now(),
        ]);

        return $this->redirectToTab($user, 'security', 'IP added to whitelist.');
    }

    public function destroyIpWhitelist(User $user, IpWhitelist $ipWhitelist): RedirectResponse
    {
        abort_unless($ipWhitelist->apiClient?->client_user_id === $user->id, 404);

        $ipWhitelist->delete();

        return $this->redirectToTab($user, 'security', 'IP removed from whitelist.');
    }

    public function storeWebhook(Request $request, User $user): RedirectResponse
    {
        $apiClient = ApiClient::where('client_user_id', $user->id)->firstOrFail();

        $data = $this->validated(Validator::make($request->all(), [
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
        ]), $user, 'security');

        WebhookSubscription::create([
            'api_client_id' => $apiClient->id,
            'url' => $data['url'],
            'events' => $data['events'],
            'secret' => \Illuminate\Support\Str::random(32),
            'is_active' => true,
        ]);

        return $this->redirectToTab($user, 'security', 'Webhook added.');
    }

    public function destroyWebhook(User $user, WebhookSubscription $webhook): RedirectResponse
    {
        abort_unless($webhook->apiClient?->client_user_id === $user->id, 404);

        $webhook->delete();

        return $this->redirectToTab($user, 'security', 'Webhook removed.');
    }

    // ---------------------------------------------------------------
    // Managerial services — warehouse/COD access, insurance agreement,
    // invoice terms, SLA commitments — account-level
    // ---------------------------------------------------------------

    public function updateManagerial(Request $request, User $user, ClientAccount $account): RedirectResponse
    {
        abort_unless($account->client_user_id === $user->id, 404);

        $data = $this->validated(Validator::make($request->all(), [
            'warehouse_access' => 'sometimes|boolean',
            'warehouse_charge' => 'required_if:warehouse_access,1|nullable|numeric|min:0',
            'cod_enabled' => 'sometimes|boolean',
            'cod_percentage' => 'required_if:cod_enabled,1|nullable|numeric|min:0|max:100',
            'staff_management_enabled' => 'sometimes|boolean',
            'staff_management_charge' => 'required_if:staff_management_enabled,1|nullable|numeric|min:0',
            'insurance_agreement' => 'sometimes|boolean',
            'insurance_agreement_date' => 'nullable|date',
            'insurance_agreement_notes' => 'nullable|string|max:2000',
            'sla_pickup_hours' => 'nullable|integer|min:0|max:720',
            'sla_delivery_days' => 'nullable|integer|min:0|max:90',
        ]), $user, 'managerial', 'managerial' . $account->id);

        $data['warehouse_access'] = $request->boolean('warehouse_access');
        $data['warehouse_charge'] = $data['warehouse_access'] ? $data['warehouse_charge'] : null;
        $data['cod_enabled'] = $request->boolean('cod_enabled');
        $data['cod_percentage'] = $data['cod_enabled'] ? $data['cod_percentage'] : null;
        $data['staff_management_enabled'] = $request->boolean('staff_management_enabled');
        $data['staff_management_charge'] = $data['staff_management_enabled'] ? $data['staff_management_charge'] : null;
        $data['insurance_agreement'] = $request->boolean('insurance_agreement');

        $account->update($data);

        return $this->redirectToTab($user, 'man, $account)gerial', "Managerial settings updated for \"{$account->account_name}\".");
    }

    /**
     * Every billing-related (and now every other) form on the Client
     * Hub carries a hidden active_tab field so a save — success or
     * failure — returns to whichever tab the person was actually
     * working in, not always Overview. Used for SUCCESS redirects;
     * see validated() for the matching FAILURE-path fix.
     */
    /**
     * $account is optional — most actions on this page still operate
     * on whichever account is Default, and for those this behaves
     * exactly as before. Actions that take an EXPLICIT account
     * (Billing Setup's methods, and now Department/Users/Managerial
     * services too) should pass it through: without it, saving
     * anything on a non-default account would redirect back to
     * clients.show, which always resolves to the Default account —
     * silently dropping the person back onto the wrong account right
     * after they just finished editing a different one.
     */
    private function redirectToTab(User $user, string $tab, string $status, ?ClientAccount $account = null): RedirectResponse
    {
        if ($account && ! $account->is_default) {
            return redirect()->route('clients.accounts.show', ['user' => $user, 'account' => $account, 'tab' => $tab])->with('status', $status);
        }

        return redirect()->route('clients.show', ['user' => $user, 'tab' => $tab])->with('status', $status);
    }

    /**
     * Replaces $validator->validate() everywhere on this page. On
     * failure, sets an EXPLICIT redirect target on the
     * ValidationException itself rather than relying on Laravel's
     * default back()-based redirect — same reasoning as the earlier
     * fix for Create Shipment's fragile back() behavior: an explicit
     * destination can never be lost to a missing Referer header or a
     * confused session state, which is exactly what was sending every
     * failed save back to Overview regardless of which tab it came
     * from. old() input and field-level $errors are still populated
     * exactly the way Laravel's default validate() would — only WHERE
     * the redirect lands changes.
     *
     * $errorBag: pass a name when this page has more than one form
     * sharing the same field names (the three special-rate forms all
     * have min_weight, base_charge, etc.) — without it, a failure in
     * one form would light up the same-named field in the others too,
     * since Laravel's default error bag is unnamed and shared.
     */
    private function validated(\Illuminate\Validation\Validator $validator, User $user, string $tab, ?string $errorBag = null): array
    {
        if ($validator->fails()) {
            $exception = \Illuminate\Validation\ValidationException::withMessages($validator->errors()->toArray())
                ->redirectTo(route('clients.show', ['user' => $user, 'tab' => $tab]));

            // Named error bags matter here specifically because the
            // Billing Setup tab has THREE special-rate forms (Standard/
            // O2D/Fleet) sharing the same field names (min_weight,
            // base_charge, etc.) on the same page — without a bag,
            // Laravel's default unnamed $errors would make a failure in
            // one form incorrectly light up the same-named field in the
            // other two as well.
            if ($errorBag) {
                $exception->errorBag($errorBag);
            }

            throw $exception;
        }

        return $validator->validated();
    }

    /**
     * Every action that targets "the" account (until multi-account
     * selection UI exists) goes through this, so a client somehow
     * missing a Default Account gets a clear error instead of a null
     * pointer three lines into an update.
     */
    /**
     * Mirrors StandardBillingController::rejectIfOverlapping() exactly
     * — same closed-interval overlap test, same reasoning for why
     * touching endpoints (0–20 and 20–40) count as overlapping (a
     * shipment at exactly 20kg would otherwise match two special
     * tariffs at once) — scoped additionally by client_account_id,
     * since two DIFFERENT accounts having special rates in the same
     * weight range for the same service type isn't a conflict at all,
     * only two rates on the SAME account are.
     */
    private function rejectIfSpecialTariffOverlapping($validator, string $errorField, int $clientAccountId, int $serviceTypeId, float $min, float $max, ?ClientSpecialTariff $ignoring = null): void
    {
        $others = ClientSpecialTariff::where('client_account_id', $clientAccountId)
            ->where('service_type_id', $serviceTypeId)
            ->where('is_active', true)
            ->when($ignoring, fn ($query) => $query->where('id', '!=', $ignoring->id))
            ->get(['min_weight', 'max_weight_limit']);

        foreach ($others as $tariff) {
            if ($this->rangesOverlap($min, $max, (float) $tariff->min_weight, (float) $tariff->max_weight_limit)) {
                $validator->errors()->add(
                    $errorField,
                    "This range ({$min}–{$max}kg) overlaps another special rate for this service type ({$tariff->min_weight}–{$tariff->max_weight_limit}kg)."
                );
            }
        }
    }

    /**
     * Same overlap test as the client-specific O2D/Fleet checks below
     * it and StandardBillingController's own version — additionally
     * matched by exact route (state/city/country on both ends) for
     * O2D, plus vehicle type for Fleet, since two DIFFERENT routes (or
     * vehicle types) sharing a weight range aren't actually in
     * conflict — only the same route/vehicle type is.
     */
    private function rangesOverlap(float $minA, float $maxA, float $minB, float $maxB): bool
    {
        return $minA <= $maxB && $minB <= $maxA;
    }

    private function rejectIfOriginDestinationTariffOverlapping($validator, string $errorField, int $clientAccountId, array $data, float $min, float $max, ?\App\Models\ClientOriginDestinationTariff $ignoring = null): void
    {
        $others = \App\Models\ClientOriginDestinationTariff::where('client_account_id', $clientAccountId)
            ->where('service_type_id', $data['service_type_id'])
            ->where('origin_state_id', $data['origin_state_id'])
            ->where('origin_city_id', $data['origin_city_id'] ?? null)
            ->where('origin_country_id', $data['origin_country_id'])
            ->where('destination_state_id', $data['destination_state_id'])
            ->where('destination_city_id', $data['destination_city_id'] ?? null)
            ->where('destination_country_id', $data['destination_country_id'])
            ->where('is_active', true)
            ->when($ignoring, fn ($query) => $query->where('id', '!=', $ignoring->id))
            ->get(['min_weight', 'max_weight_limit']);

        foreach ($others as $tariff) {
            if ($this->rangesOverlap($min, $max, (float) $tariff->min_weight, (float) $tariff->max_weight_limit)) {
                $validator->errors()->add(
                    $errorField,
                    "This range ({$min}–{$max}kg) overlaps another special rate for this exact route ({$tariff->min_weight}–{$tariff->max_weight_limit}kg)."
                );
            }
        }
    }

    private function rejectIfFleetTariffOverlapping($validator, string $errorField, int $clientAccountId, array $data, float $min, float $max, ?\App\Models\ClientFleetBillingTariff $ignoring = null): void
    {
        $others = \App\Models\ClientFleetBillingTariff::where('client_account_id', $clientAccountId)
            ->where('service_type_id', $data['service_type_id'])
            ->where('vehicle_type_id', $data['vehicle_type_id'])
            ->where('origin_state_id', $data['origin_state_id'])
            ->where('origin_city_id', $data['origin_city_id'] ?? null)
            ->where('origin_country_id', $data['origin_country_id'])
            ->where('destination_state_id', $data['destination_state_id'])
            ->where('destination_city_id', $data['destination_city_id'] ?? null)
            ->where('destination_country_id', $data['destination_country_id'])
            ->where('is_active', true)
            ->when($ignoring, fn ($query) => $query->where('id', '!=', $ignoring->id))
            ->get(['min_weight', 'max_weight_limit']);

        foreach ($others as $tariff) {
            if ($this->rangesOverlap($min, $max, (float) $tariff->min_weight, (float) $tariff->max_weight_limit)) {
                $validator->errors()->add(
                    $errorField,
                    "This range ({$min}–{$max}kg) overlaps another special rate for this exact vehicle type/route ({$tariff->min_weight}–{$tariff->max_weight_limit}kg)."
                );
            }
        }
    }

    /**
     * Same "state XOR country" normalization the store/update actions
     * apply to validated data — needed a second time here since the
     * overlap check runs inside validator->after(), before validated()
     * has actually returned the normalized $data.
     */
    private function normalizeRouteFields(array $raw): array
    {
        return [
            'service_type_id' => $raw['service_type_id'] ?? null,
            'vehicle_type_id' => $raw['vehicle_type_id'] ?? null,
            'origin_state_id' => ($raw['origin_type'] ?? null) === 'country' ? null : ($raw['origin_state_id'] ?? null),
            'origin_city_id' => ($raw['origin_type'] ?? null) === 'country' ? null : ($raw['origin_city_id'] ?? null),
            'origin_country_id' => ($raw['origin_type'] ?? null) === 'country' ? ($raw['origin_country_id'] ?? null) : null,
            'destination_state_id' => ($raw['destination_type'] ?? null) === 'country' ? null : ($raw['destination_state_id'] ?? null),
            'destination_city_id' => ($raw['destination_type'] ?? null) === 'country' ? null : ($raw['destination_city_id'] ?? null),
            'destination_country_id' => ($raw['destination_type'] ?? null) === 'country' ? ($raw['destination_country_id'] ?? null) : null,
        ];
    }

    private function requireDefaultAccount(User $user): ClientAccount
    {
        abort_unless($user->user_type === 'client', 404);
        $account = $user->defaultAccount;
        abort_unless($account, 404, 'This client has no account set up yet.');

        return $account;
    }

    /**
     * $existingAccount is the account being updated (null when
     * creating) — only used to know whether to delete an old logo
     * file when a new one's uploaded, and to fall back to the
     * account's own state_id when resolving a typed city (see
     * resolveCity()).
     */
    private function accountData(Request $request, array $data, ?ClientAccount $existingAccount): array
    {
        $stateId = $data['state_id'] ?? $existingAccount?->state_id;
        [$cityId, $cityName] = $this->resolveCity($data['city_name'] ?? null, $stateId);

        $logoPath = $existingAccount?->logo_path;
        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $request->file('logo')->store('client-logos', 'public');
        }

        // Territory is derived from State, never chosen independently
        // — every State already belongs to exactly one Territory, so
        // letting someone pick a mismatched one would just be bad data.
        $territoryId = $stateId ? \App\Models\State::find($stateId)?->territory_id : null;

        return [
            'account_type' => $data['account_type'],
            'id_type' => $data['account_type'] === 'individual' ? ($data['id_type'] ?? null) : null,
            'id_number' => $data['account_type'] === 'individual' ? ($data['id_number'] ?? null) : null,
            'company_name' => $data['account_type'] === 'organization' ? ($data['company_name'] ?? null) : null,
            'logo_path' => $data['account_type'] === 'organization' ? $logoPath : null,
            'rc_number' => $data['account_type'] === 'organization' ? ($data['rc_number'] ?? null) : null,
            // tin ("Client Tax ID") and contact_person_name are usable
            // on any account type now, edited from the Accounts tab
            // rather than this form — this form simply doesn't carry
            // either field for an individual account, so preserve
            // whatever's already saved instead of treating "not on
            // this form" as "clear it".
            'tin' => $request->has('tin') ? ($data['tin'] ?? null) : $existingAccount?->tin,
            'industry' => $data['industry'] ?? null,
            'contact_person_name' => $request->has('contact_person_name') ? ($data['contact_person_name'] ?? null) : $existingAccount?->contact_person_name,
            'contact_person_role' => $data['account_type'] === 'organization' ? ($data['contact_person_role'] ?? null) : null,
            'address' => $data['address'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'state_id' => $stateId,
            'city_id' => $cityId,
            'city_name' => $cityName,
            'outlet_id' => $data['outlet_id'] ?? null,
            'territory_id' => $territoryId,
            'business_objective' => $data['business_objective'] ?? null,
            'alternate_phone' => $data['alternate_phone'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'business_manager_id' => $data['business_manager_id'] ?? null,
        ];
    }

    /**
     * A typed city that matches an existing row (by name, within the
     * chosen State) resolves to that row's id — the real relationship;
     * anything else is kept as free text (city_name) rather than
     * blocking on the cities table already having their exact city.
     *
     * @return array{0: int|null, 1: string|null} [city_id, city_name]
     */
    private function resolveCity(?string $typed, ?int $stateId): array
    {
        $typed = trim((string) $typed);

        if ($typed === '') {
            return [null, null];
        }

        $match = City::where('state_id', $stateId)->whereRaw('LOWER(name) = ?', [strtolower($typed)])->first();

        return $match ? [$match->id, null] : [null, $typed];
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
            'logo' => 'nullable|image|max:2048',
            'rc_number' => $accountType === 'organization' ? 'required|string|max:255' : 'nullable|string|max:255',
            'tin' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'contact_person_name' => $accountType === 'organization' ? 'required|string|max:255' : 'nullable|string|max:255',
            'contact_person_role' => 'nullable|string|max:255',

            'address' => 'nullable|string|max:1000',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_name' => 'nullable|string|max:255',
            'outlet_id' => 'nullable|exists:outlets,id',
            'business_objective' => 'nullable|string|max:2000',
            'alternate_phone' => 'nullable|string|max:30',
            'billing_address' => 'nullable|string|max:1000',
            'business_manager_id' => 'nullable|exists:users,id',
        ]);

        $data = $validator->validate();
        unset($data['logo']); // handled separately in accountData() — needs $request->file(), not the validated array

        return $data;
    }
}
