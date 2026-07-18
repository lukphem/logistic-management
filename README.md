<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Increment 3 — Api Controllers

Adds the actual controllers behind every route in `routes/api.php`, plus
the supporting tables they need:

- `AuthController` — single login endpoint issuing Sanctum tokens (ability
  scoped to `user_type`); `Role`/`PermissionController` wrap
  spatie/laravel-permission
- `ShipmentController` (staff), `RateController` (staff — includes a
  `zone-price` action to upsert one zone-to-zone matrix entry at a time),
  `ReportController::exceptions`
- `RiderController` — assigned orders, scan/status update (one action
  handles both, since a scan IS the status update in practice), live
  location ping (`rider_locations` — latest position only, not a history
  log), COD remittance, basic earnings count
- `ClientController` + `ClientShipmentController` — shared by the JWT
  client-portal group and the API-key `/integration` group; requester
  identity resolves to either `$request->user()` or the `api_client`
  attribute set by `CheckIpWhitelist`, so the exact same code path serves
  both audiences
- `WebhookController::subscribe` — stores the callback URL, subscribed
  events, and a signing secret; actual dispatch (queued HMAC-signed POST
  on status change) is deferred to the notification-service increment
- New tables: COD fields on `shipments`; `rider_locations`;
  `client_wallets` + `wallet_transactions`; `webhook_subscriptions`
- `RolePermissionSeeder` — seeds the module/action permission set
  (shipments, rates, riders, reports, settings, roles × create/read/update/delete)
  and five default roles (Super Admin, Ops Manager, Hub Staff, Finance,
  Support); `DatabaseSeeder` now calls it and assigns the test user
  Super Admin

### Config note

spatie/laravel-permission's published config defaults to the `web` guard.
Since all API auth here goes through Sanctum, set
`'default' => 'sanctum'` under the `guards` key considerations in
`config/permission.php`, or explicitly pass `guard_name: 'sanctum'`
wherever roles/permissions are created (already done in the seeder and
controllers above) — the important part is that it's consistent
everywhere, since a mismatch will make `hasRole()`/`can()` checks silently
fail.

### Still not built

- Notification/webhook dispatch job (queued, HMAC-signed)
- SLA breach detection scheduled job
- Waybill generation (thermal ZPL + A4 PDF)
- Formal invoice documents (currently just a shipment-list view)

## Increment 4 — Staff Admin Dashboard (Blade)

First frontend increment. Session-based (not Sanctum) auth, since this is
a server-rendered dashboard for staff only — riders and clients never get
a web session.

**Design approach:** brand primary/secondary colors are injected as CSS
custom properties at the layout root (`components/layouts/app.blade.php`),
read live from `config('branding.colors')` — this is the entire per-client
theming mechanism from earlier discussions, now implemented. Static tokens
(status colors, ink/surface/line neutrals, the mono font used for tracking
numbers) live in `resources/css/app.css`'s `@theme` block since those are
compiled by Tailwind and aren't client-configurable.

**Signature element:** the shipment detail page's checkpoint trail
(`shipments/show.blade.php`) is styled as a waybill stamp trail — dashed
square "stamps" alternating a slight rotation, connected by a dashed line —
rather than a generic dot-and-line timeline, since the subject (a courier
waybill) already has its own physical vocabulary of stamps and checkpoints.

### Files

```
app/Http/Controllers/Web/Auth/LoginController.php
app/Http/Controllers/Web/DashboardController.php
app/Http/Controllers/Web/ShipmentController.php
app/Http/Middleware/EnsureStaffUser.php   (registered as 'staff' alias)
resources/views/components/layouts/app.blade.php
resources/views/components/status-pill.blade.php
resources/views/auth/login.blade.php
resources/views/dashboard/index.blade.php
resources/views/shipments/index.blade.php
resources/views/shipments/show.blade.php
routes/web.php                             (replaces the default welcome route)
resources/css/app.css                      (adds status/ink/surface/mono tokens)
bootstrap/app.php                          (registers the 'staff' middleware alias)
```

### Try it locally

```powershell
npm install
npm run dev    # or npm run build for production assets
php artisan serve
```

Sign in with the seeded test user (`test@example.com`) — set its password
first via Tinker, since the factory generates a random hashed one:

```powershell
php artisan tinker
>>> $u = \App\Models\User::where('email', 'test@example.com')->first();
>>> $u->password = bcrypt('password');
>>> $u->save();
```

### Still to come (frontend)

- Rate card management screens (this needs the most UI thought — the
  `model_config` form fields change shape per billing_model)
- Roles & permissions management screen
- Reports/exceptions screen
- Client self-service portal (separate Blade area, different layout shell)
- Rider mobile app (separate stack entirely — not Blade)

## Increment 5 — System Setup Page

The onboarding/setup wizard from the original feature spec, implemented:
a staff-only `/settings` page backed by a real `settings` table (single
row — this is a single-tenant deployment, so there's exactly one company's
configuration, not a per-tenant table).

**How it wires in:** `BrandingServiceProvider` overlays the saved settings
onto `config('branding.*')` at boot, so every existing call site (the
layout's brand-color injection, `ShipmentPricingService`'s VAT lookup)
keeps working unchanged — nothing had to be refactored to read from the
database instead of the config file. `config/branding.php` still holds the
fallback defaults for a fresh install before anyone has saved anything.

### Files

```
database/migrations/2026_01_04_000001_create_settings_table.php
app/Models/Setting.php
app/Providers/BrandingServiceProvider.php   (registered in bootstrap/providers.php)
app/Http/Controllers/Web/SettingsController.php
resources/views/settings/edit.blade.php
```

### What it covers

- Company name, operating regions
- Service names (Express / Same-Day / Economy labels — these map to the
  `service_type` field used when configuring rate cards)
- Primary/secondary brand colors (color picker, takes effect immediately
  on save — no rebuild needed)
- VAT percentage, currency
- Waybill thermal label size (2×1 / 4×6) and QR toggle

### Not yet included

- Logo upload (currently text-only company name; add a file input +
  storage disk config when waybill/PDF generation needs an actual image)
- Hub/zone setup screens (separate from this page — hubs/zones are
  per-location, not single-value settings)
- Permission-gating who can reach `/settings` beyond "any staff account" —
  add a `settings:update` permission check once role assignment UI exists

## Increment 6 — Full Settings Setup (Logo, Locations, Permission-Gating)

Completes the setup module started in Increment 5.

### Guard fix (important — read this one)

`RolePermissionSeeder` previously seeded all permissions/roles under a
single `sanctum` guard. That's fine for the API, but the Blade admin
authenticates via the `web` session guard — Spatie checks permissions
per-guard, so a `web`-guard request checking a `sanctum`-only permission
silently fails every time, with no obvious error. The seeder now creates
every permission and role under **both** `web` and `sanctum`, and
`DatabaseSeeder` assigns Super Admin under both for the test user. If you
already ran the old seeder, re-run it — `firstOrCreate`/`syncPermissions`
make it safe to run again.

### Logo upload

- `settings.logo` (file) → stored on the `public` disk under
  `storage/app/public/branding/`, path saved as `logo_path`
- `Setting::logo_url` accessor resolves the public URL; old file is
  deleted when a new one is uploaded
- Sidebar and login page now show the uploaded logo, falling back to
  initials when none is set
- **Requires** `php artisan storage:link` to be run once, or uploaded
  logos will save but not be reachable over HTTP

### Location setup (Hubs & Zones)

Full CRUD screens for hubs/branches and zones/regions — this is the
"location setup" from the original feature spec, previously only
API-backed:

```
app/Http/Controllers/Web/HubController.php
app/Http/Controllers/Web/ZoneController.php
resources/views/hubs/index.blade.php, hubs/form.blade.php
resources/views/zones/index.blade.php, zones/form.blade.php
```

Zones can optionally belong to a hub (dropdown on the zone form). A new
`locations` permission module (create/read/update/delete) gates every
route.

### Permission-gating

`/settings` now requires `settings:update`; hub/zone routes require the
matching `locations:*` permission per action. Sidebar nav items hide
themselves automatically for users without the relevant permission
(`@continue` check against `auth()->user()->cannot(...)`) — so a Finance
user, for example, sees Dashboard and Shipments but not Hubs/Zones/Settings.

### To apply locally

```powershell
php artisan storage:link
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

## Increment 7 — Settings Module Completed (Invoicing + Scan Statuses)

Closes the last two gaps from the original setup-wizard spec that
Increment 6 didn't cover.

### Invoicing

- `invoice_header` / `invoice_footer` (free text) added to `settings`,
  exposed on the setup page, overlaid onto
  `config('branding.invoice.header'/'footer')` for whenever invoice/PDF
  generation is built

### Configurable scan statuses

- `scan_statuses` table: `key` (stable, stored on shipments/scan_events —
  never editable once created), `label` (editable), `sort_order`
  (editable, drives display order), `is_terminal` (marks
  delivered/cancelled/returned-style end states)
- `ScanStatusSeeder` populates the same 9 default statuses used
  throughout the API/dashboard so far (booked → delivered/exception/etc.)
- `/scan-statuses` page: inline edit label/order/terminal-flag per row,
  plus an add-new-status form. Gated to `settings:update`, same as the
  general settings page — this is still "system setup," not a separate
  permission module.

### Files

```
database/migrations/2026_01_04_000002_add_invoice_fields_to_settings_table.php
database/migrations/2026_01_04_000003_create_scan_statuses_table.php
app/Models/ScanStatus.php
database/seeders/ScanStatusSeeder.php        (called from DatabaseSeeder)
app/Http/Controllers/Web/ScanStatusController.php
resources/views/scan-statuses/index.blade.php
```

### To apply locally

```powershell
php artisan migrate
php artisan db:seed --class=ScanStatusSeeder
```

### Note on ScanEvent/Shipment status columns

`ScanEvent.status` and `Shipment.current_status` are still plain strings
(from Increment 2) — they store the `key`, not a foreign key to
`scan_statuses`. That's deliberate: relabeling a status here never
touches historical records. If you later want referential integrity
instead (so an invalid key can't be scanned), that's a bigger change —
flag it if you want that tightened.

### Settings module — now complete

Company profile, logo, service names, branding colors, VAT/currency,
invoicing header/footer, waybill design, and scan-status list are all
editable from the dashboard. Hub/zone location setup lives in its own
screens (Increment 6) rather than on the settings page itself, since
those are per-location records, not single values.

## Increment 8 — Branded Error Pages

Replaces Laravel's default error pages with ones matching the dashboard's
look and giving the person a plain explanation of what happened.

### Files

```
resources/views/components/error-page.blade.php   (shared shell — doesn't assume an authenticated user)
resources/views/errors/401.blade.php
resources/views/errors/403.blade.php
resources/views/errors/404.blade.php
resources/views/errors/419.blade.php   (CSRF token expired — the most common cause of an unexplained failed form submit)
resources/views/errors/429.blade.php   (rate limited)
resources/views/errors/500.blade.php
resources/views/errors/503.blade.php   (maintenance mode)
```

Laravel auto-resolves these by HTTP status code — no route or controller
changes needed. `error-page.blade.php` shows "Sign in" for guests and
"Back to dashboard" for authenticated users, since 404/419/500 can happen
to either.

### One thing to know about local testing

With `APP_DEBUG=true` (typical in local `.env`), Laravel still shows these
custom views for 401/403/404/419/429 — but a genuine 500-level exception
will show Laravel's detailed debug page (Ignition) instead, by design, so
you can see the stack trace while developing. The custom `500.blade.php`
only takes over once `APP_DEBUG=false` (production). To see it locally,
temporarily set `APP_DEBUG=false` in `.env` and clear config cache
(`php artisan config:clear`).

### About your 403 just now

That's expected if the signed-in user's role doesn't include the
`settings:update`/`locations:*`/etc. permission a route requires — see
Increment 6's guard fix. Check which role the test account has:

```powershell
php artisan tinker
>>> \App\Models\User::where('email', 'test@example.com')->first()->getRoleNames();
```

If it doesn't list "Super Admin", re-run the seeders from Increment 6/7.

## Increment 9 — Setups Menu Consolidation + Visual Refresh

### Navigation restructure

All setup screens now live under a single collapsible **Setups** menu
item in the sidebar, ordered by dependency rather than alphabetically:

1. **Company Settings** — no prerequisites, configure first
2. **Hubs & Branches** — no prerequisites
3. **Zones** — optionally references a hub, so hubs should exist first
4. **Scan Statuses** — independent, but this is still setup, not a
   day-to-day operational screen

The group is a native `<details>`/`<summary>` element — no JS dependency,
fully keyboard/accessible by default — and auto-expands when the active
page is one of its children. It hides entirely for a user (e.g. Support
role) who holds none of the underlying permissions, same as before.

### Visual refresh

- Added `resources/views/components/icon.blade.php` — a small set of
  hand-drawn line icons (dashboard, box, setups/gear, building, layers,
  list-check, sliders, chevron, logout, search) so nav items read at a
  glance instead of relying on a plain dot. No icon library dependency
  added.
- Sidebar: subtle depth gradient, thicker active-item accent bar, smooth
  color transitions on hover instead of instant state changes
- Topbar: translucent/blurred on scroll, shows a small "Setups" eyebrow
  label above the page title when inside any setup screen — so someone
  deep in "Zones" still sees they're in Setups without checking the
  sidebar
- All card containers repo-wide: `rounded-lg` → `rounded-xl` with a
  subtle `shadow-sm`, for a softer, more modern surface than sharp
  corners with only a hairline border
- Table row hovers now transition smoothly rather than snapping

### Files

```
resources/views/components/icon.blade.php
resources/views/components/layouts/app.blade.php   (nav restructure + visual pass)
+ minor radius/transition touch-ups across dashboard, shipments, hubs, zones,
  scan-statuses, settings, and login views
```

No backend changes, no migration needed for this increment.

## Increment 10 — Billing Setup (Rate Cards UI + Standard/Special Client Billing)

### The Standard/Special model, exactly as specified

Every client is **Standard** by default — no row needed in
`client_billing_profiles` at all; "no profile" and "explicitly standard"
are treated identically (zero discount). A client can be put on
**Special**, which stores a `discount_percentage` — not a frozen price.

`ShipmentPricingService::priceShipment()` now takes an optional
`ClientBillingProfile` and applies the discount to **freight + surcharges
only** (insurance is a pass-through cost, never discounted), then
recalculates VAT on the discounted subtotal. Because the discount is
applied fresh against whatever the standard `RateCard` resolves to at
quote time — never against a number saved when the agreement was made —
raising the standard rate automatically raises every special client's
price too. Only touching `discount_percentage` itself changes their
relative price.

`ClientBillingProfile::resolveForRequest()` figures out who's asking
(portal user via session/Sanctum, or external integrator via the
`api_client` request attribute set by `CheckIpWhitelist`) and both
`ClientController::quote()` and `ClientShipmentController::store()` now
resolve and apply it automatically — no extra parameter for callers to
pass.

### Rate Card management (staff, the "standard rate" itself)

`/rate-cards` — full CRUD. The form shows only the config fields relevant
to whichever billing model is selected (flat/distance/weight/volumetric/
hybrid/service_multiplier/time_surcharge/contract), toggled with a small
vanilla-JS show/hide — no new frontend dependency. Zone-to-zone rate
cards get a dedicated matrix editor on the edit page (add/remove one
origin→destination price pair at a time), gated the same way as the rest
of the form.

### Client Billing (staff, per-client Standard/Special assignment)

`/client-billing` lists every client — portal users and external API
integrations side by side — with their current billing status and
discount. Editing a client is two radio options (Standard / Special) with
the discount field only appearing when Special is selected; switching
back to Standard always zeroes the stored discount rather than leaving a
stale value that could resurface later.

### New permission module

`billing` (create/read/update/delete) added alongside the existing
modules. Finance's default role now includes full `rates` access and
`billing` (they set rates and negotiate client discounts); Ops Manager
gained `rates:read` (needs to see prices, not set them).

### Files

```
database/migrations/2026_01_05_000001_create_client_billing_profiles_table.php
database/migrations/2026_01_05_000002_add_discount_amount_to_shipments_table.php
app/Models/ClientBillingProfile.php
app/Http/Controllers/Web/RateCardController.php
app/Http/Controllers/Web/ClientBillingController.php
resources/views/rate-cards/index.blade.php, rate-cards/form.blade.php
resources/views/client-billing/index.blade.php, client-billing/edit.blade.php
```

### To apply locally

```powershell
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

### Known gap

Staff-initiated walk-in bookings (`ShipmentController::store` — the
staff-facing, non-client-portal booking endpoint) still create a shipment
without running it through `ShipmentPricingService`, so pricing and any
special discount aren't applied there yet. Flag it if walk-in bookings
need to go live before that's wired up.

## Increment 11 — Staff User Management + Roles & Permissions

### Staff users (`/users`)

Manages dashboard accounts only — riders and clients authenticate
differently and are out of scope here on purpose. Create/edit a staff
user, assign them one role from a dropdown, set a password (optional on
edit — blank leaves it unchanged), and toggle active/deactivated. A user
can't deactivate or delete their own account (checked in the controller).

### Roles & Permissions (`/roles`)

Edit permissions on the five seeded default roles, or create a custom one
(e.g. "Regional Supervisor" — the exact example from the original spec).
Permissions render as checkboxes grouped by module with a "Select all"
per group. The five defaults (Super Admin, Ops Manager, Hub Staff,
Finance, Support) are protected from deletion — the delete action is
simply hidden for those rows — since removing one would silently strip
access from every staff account assigned to it.

### The two-guard detail (important if you touch this code)

Every role/permission name exists as **two** Spatie rows — one for the
`web` guard (dashboard sessions), one for `sanctum` (API/mobile) — per
the guard fix from Increment 6. Both controllers here treat that as
exactly one decision from the user's point of view:

- Creating/editing a role writes permissions to both guard rows together
- Assigning a role to a staff user assigns both guard versions
- The `web` row is the only one ever shown or bound to in a route —
  `sanctum`'s copy is kept in sync automatically, never exposed in the UI

If you add a new controller that touches roles or permissions, follow
this same pattern rather than operating on a single guard — that's
exactly the class of bug the Increment 6 guard fix corrected.

### New permission module

`users` (create/read/update/delete) — currently granted only to Super
Admin (`*`). No other default role can create staff accounts or edit
roles out of the box; that's deliberate.

### Files

```
app/Http/Controllers/Web/UserController.php
app/Http/Controllers/Web/RoleController.php
resources/views/users/index.blade.php, users/form.blade.php
resources/views/roles/index.blade.php, roles/form.blade.php
```

### To apply locally

```powershell
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

(No new tables — this uses spatie/laravel-permission's existing tables
and the `users` table's existing columns.)

### Settings module — status

With this increment, every screen from the original setup-wizard spec
plus the access-control layer (roles, permissions, staff accounts) is
built: Company Settings, Hubs, Zones, Rate Cards, Client Billing, Scan
Statuses, Roles & Permissions, and Staff Users — all grouped under one
Setups menu, ordered by dependency.

## Increment 12 — Access Level (Global/Hub) + Account Lock/Suspend/Terminate + Audit Trail

### Bug fix: missing `sanctum` guard

While investigating the "Roles and permissions not accessible" report,
found that `config/auth.php` never had a `sanctum` guard entry —
`auth:sanctum` middleware (used across `routes/api.php` since Increment
3) needs `config('auth.guards.sanctum')` to resolve, and Spatie's
per-guard permission lookups for `sanctum`-guard roles need it too. This
is now added. **If you're still seeing the roles/permissions problem
after applying this and re-seeding, tell me the exact error text or
screen you get** — I couldn't reproduce it directly (no PHP runtime in
the environment this was built in), so I can't confirm this was the only
cause.

### Access level: Global vs. hub-restricted

A single nullable `hub_id` on `users` — `null` means global access (every
hub's shipments), set means restricted to that one hub. `User::hasGlobalAccess()`
is the one place that logic lives. `ShipmentController` (staff) now
filters both the list and the individual-shipment view by the signed-in
user's hub when they're not global — a hub-restricted user gets a 403 on
a shipment outside their hub, not just a filtered list that happens to
exclude it.

The staff user form presents this as two radio options (Global /
Specific hub) rather than exposing the raw column — picking "Specific
hub" reveals a hub dropdown.

**Not yet extended to:** rider assignment, reports, or rate cards. Those
still show everything regardless of the viewer's hub. Flag it if any of
those need the same restriction next.

### Account status: active / suspended / locked / terminated

Replaces the old binary `is_active` toggle (still present and kept in
sync, since a couple of other places read it) with four explicit states.
All three restrictive states block login identically today; they're
distinct because "why can't this person sign in" should show suspended
vs. terminated vs. locked, not just "inactive" — for the exact audit and
security-review reason you asked for.

- **Suspended / Locked / Terminated** — require a reason, always
- **Reactivate** — reason optional
- A user can't change their own status (or delete their own account)
- Checked on **every request**, not just at login — `UserType` middleware
  (API/Sanctum) and `EnsureStaffUser` (dashboard session) both check
  `canSignIn()` per-request, so suspending someone mid-session cuts them
  off immediately rather than waiting for their token/session to expire.
  The API side also explicitly deletes their current access token.

### Audit trail

`user_status_audits` — append-only, never updated or deleted. Every
status change writes `from_status`, `to_status`, `reason`, and
`changed_by`. `User::changeStatus()` is the only place that writes to it,
so the audit log and the current status can never drift apart — they're
one atomic operation. The user's edit page shows the full history.

### Files

```
config/auth.php                                              (sanctum guard fix)
database/migrations/2026_01_06_000001_add_access_scope_and_account_status_to_users_table.php
database/migrations/2026_01_06_000002_create_user_status_audits_table.php
app/Models/UserStatusAudit.php
app/Models/User.php            (hub(), statusAudits(), hasGlobalAccess(), canSignIn(), changeStatus())
app/Http/Controllers/Web/UserController.php   (rewritten)
app/Http/Controllers/Web/ShipmentController.php  (hub-scoping added)
app/Http/Controllers/Web/Auth/LoginController.php
app/Http/Controllers/Api/AuthController.php
app/Http/Middleware/EnsureStaffUser.php
app/Http/Middleware/UserType.php
resources/views/users/index.blade.php, users/form.blade.php
```

### To apply locally

```powershell
php artisan migrate
```

(No reseed needed for this one — no new permission modules.)

## Increment 13 — Regions: A Third Access-Scope Level

Extends the access scale from Increment 12 (Global / Hub) into three
levels: **Global > Region > Hub (Station)**. A Region groups multiple
Hubs — the exact "region contains multiple stations" hierarchy asked for.

### Data model

- `regions` table (name, code) — sits above Hub
- `hubs.region_id` (nullable) — a hub optionally belongs to one region
- `users.region_id` (nullable, alongside the existing `hub_id`) — the
  three scope levels are mutually exclusive: both null = Global,
  `region_id` set = Region, `hub_id` set = Hub. Never two at once —
  enforced in `UserController::validateForm()`, which zeroes out
  whichever field doesn't match the selected `access_scope`.

### `User` model additions

- `hasGlobalAccess()` / `hasRegionAccess()` / `hasHubAccess()` — the
  three checks
- `accessibleHubIds()` — resolves any of the three straight down to
  "which hub IDs can this person see," so `ShipmentController` (and
  anything scoped the same way later) never needs to know which level
  produced the list. Global returns every hub, Region returns every hub
  under that region, Hub returns just the one.

### Where it's enforced right now

`ShipmentController` (staff) — both the list and the individual-shipment
view now filter through `accessibleHubIds()` instead of a single hub_id
comparison. Same 403-on-out-of-scope behavior as before, now correctly
covering the region case too.

**Not yet extended to:** rider assignment, reports, rate cards — same gap
noted in Increment 12, now also applies to the region level.

### New screen

`/regions` — simple CRUD (name, code), gated under the existing
`locations:*` permission alongside Hubs and Zones, since it's the same
conceptual area. Added to the Setups menu right before Hubs & Branches,
since a hub's region picker needs regions to exist first.

The Hub form now has a region dropdown ("No region" is valid — a hub
doesn't have to belong to one). The staff user form gained a third radio
option between Global and Specific Hub.

### Files

```
database/migrations/2026_01_07_000001_create_regions_table.php
database/migrations/2026_01_07_000002_add_region_id_to_hubs_table.php
database/migrations/2026_01_07_000003_add_region_id_to_users_table.php
app/Models/Region.php
app/Models/Hub.php               (region() relation)
app/Models/User.php               (hasRegionAccess, accessibleHubIds)
app/Http/Controllers/Web/RegionController.php
app/Http/Controllers/Web/HubController.php    (region_id added)
app/Http/Controllers/Web/UserController.php   (three-way access_scope)
app/Http/Controllers/Web/ShipmentController.php  (accessibleHubIds)
resources/views/regions/index.blade.php, regions/form.blade.php
resources/views/hubs/form.blade.php, hubs/index.blade.php  (region field/column)
resources/views/users/form.blade.php, users/index.blade.php  (region option/column)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 14 — Outlets: A Fourth Level Under Hubs

Adds Outlets — agent counters, franchise points, or pickup/drop-off spots
that each report to exactly one hub. Completes the location hierarchy:
**Region > Hub > Outlet**, and the access scale becomes four levels:
**Global > Region > Hub > Outlet**.

### Data model

- `outlets` table: `hub_id` (required — unlike Hub's optional `region_id`,
  an outlet doesn't exist independently of its hub), name, code, address,
  lat/lng, active flag
- `users.outlet_id` (nullable) — the fourth scope level, mutually
  exclusive with `region_id`/`hub_id` the same way those already are with
  each other

### Important scoping detail

Shipments are tracked at **hub** granularity (`current_hub_id`), not
outlet — there's no `current_outlet_id` on shipments. So an
outlet-scoped user's `accessibleHubIds()` resolves to their outlet's
**parent hub** — they see the same shipment set as someone scoped
directly to that hub. Outlet-level access exists for staff-account
organization and future outlet-specific features (e.g. an outlet-specific
report or a dedicated outlet counter view), not to further narrow
shipment visibility below the hub level. If shipments ever need
outlet-level granularity (e.g. an agent counter scanning its own
handovers separately from the hub's), that's a schema change to
`shipments`/`scan_events` worth discussing before building — flag it if
that's actually needed.

### New screen

`/outlets` — CRUD (hub picker, name, code, address, lat/lng, active
toggle), gated under the existing `locations:*` permission alongside
Regions/Hubs/Zones. Added to the Setups menu right after Hubs & Branches,
since an outlet's hub picker needs hubs to exist first.

### Files

```
database/migrations/2026_01_08_000001_create_outlets_table.php
database/migrations/2026_01_08_000002_add_outlet_id_to_users_table.php
app/Models/Outlet.php
app/Models/Hub.php     (outlets() relation)
app/Models/User.php    (hasOutletAccess(), accessibleHubIds() updated)
app/Http/Controllers/Web/OutletController.php
app/Http/Controllers/Web/UserController.php   (four-way access_scope)
resources/views/outlets/index.blade.php, outlets/form.blade.php
resources/views/users/form.blade.php, users/index.blade.php  (outlet option/column)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 15 — Outlet-Level Shipment Visibility + Units

Two separate additions, both requested together but conceptually
distinct: shipments now actually track outlet location (so outlet-scoped
staff see something real), and Units organize staff into teams within a
hub (no effect on shipment visibility at all).

### Outlet-level shipment visibility

- `shipments.current_outlet_id` (nullable) — sits alongside the existing
  `current_hub_id`. Null means "at the hub itself"; set means physically
  at that specific outlet.
- `scan_events.outlet_id` — history now records outlet granularity too,
  not just hub.
- `RiderController::scan` (API) resolves both together: scanning with an
  `outlet_id` sets `current_outlet_id` AND looks up that outlet's parent
  hub to set `current_hub_id` too — so hub/region-scoped staff still see
  the shipment via the normal hub rollup, while outlet-scoped staff see
  it via the more specific outlet match. Scanning with only a `hub_id`
  (arriving back at the hub itself, no particular outlet) clears
  `current_outlet_id`.
- `User::canAccessShipment()` is the new precise single-shipment check —
  outlet-scoped users are matched against `current_outlet_id` directly;
  every other scope level still goes through `accessibleHubIds()`.
  `ShipmentController::show()` now uses this instead of the old
  hub-only comparison.
- Shipment list/detail views show the current outlet when set (index:
  under the route; detail: "Current location" row, plus outlet shown per
  checkpoint in the scan timeline).

This closes the gap flagged at the end of Increment 14 — outlet access
now means something concrete, not just an inherited hub-level view.

### Units — organizational sub-division within a hub

Distinct from Outlet on purpose: a Unit has no address, no GPS location,
and **never affects shipment visibility** — it's a team/department tag
(Operations, Customer Service, Dispatch, Warehouse, Finance, etc.) for
staff structure only. A hub can have both Outlets and Units,
independently — one is physical, the other organizational.

- `units` table: belongs to a hub (required), name, code
- `users.unit_id` (nullable) — **not** part of the mutually-exclusive
  access scale. It's an independent, optional tag that can be set
  alongside a Hub or Outlet access scope (shown on the form only when
  one of those two is selected, since Global/Region users aren't tied to
  a specific hub's internal structure)
- New `/units` screen, gated under the same `locations:*` permission,
  added to Setups right after Outlets

### Files

```
database/migrations/2026_01_09_000001_add_current_outlet_id_to_shipments_table.php
database/migrations/2026_01_09_000002_add_outlet_id_to_scan_events_table.php
database/migrations/2026_01_09_000003_create_units_table.php
database/migrations/2026_01_09_000004_add_unit_id_to_users_table.php
app/Models/Unit.php
app/Models/Shipment.php   (current_outlet_id, currentOutlet(), currentHub())
app/Models/ScanEvent.php  (outlet_id, outlet())
app/Models/User.php       (unit(), canAccessShipment())
app/Http/Controllers/Web/UnitController.php
app/Http/Controllers/Web/ShipmentController.php   (outlet-aware filtering)
app/Http/Controllers/Api/RiderController.php       (scan resolves hub+outlet together)
app/Http/Controllers/Web/UserController.php        (unit_id, always optional)
resources/views/units/index.blade.php, units/form.blade.php
resources/views/shipments/index.blade.php, shipments/show.blade.php  (outlet display)
resources/views/users/form.blade.php, users/index.blade.php          (unit field/column)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 16 — Staff Profile Details + Logo Display Bugfix

### Bugfix: logo image not visible

`Setting::getLogoUrlAttribute()` was building an **absolute** URL via
`Storage::disk('public')->url()`, which derives its host from `APP_URL`.
`APP_URL` in a fresh `.env` typically has no port (`http://localhost`),
while `php artisan serve` runs on `:8000` — so the generated `<img src>`
silently pointed at the wrong port and 404'd. Fixed by returning a
root-relative path (`/storage/...`) instead, which resolves against
whatever host/port the page is actually being viewed on, regardless of
`APP_URL`. Applied the same way to the new staff photo URL.

**Still required** for either logo or staff photos to actually load:
`php artisan storage:link` (creates the `public/storage` symlink) — this
was already flagged in earlier increments' README notes but is worth
repeating since it's the other half of what makes uploaded images work.

### Staff profile additions

- **Staff ID** — auto-generated (`STF-XXXXXX`) the moment a staff account
  is created, never editable, never regenerated
- **First name / Last name** — replace the single Name field on the form.
  `name` itself is kept in the database and auto-synced from these two
  (see `User::booted()`'s `saving` hook) specifically so the dozens of
  existing places that read `$user->name` — `assignedRider->name`,
  `handler->name`, dashboard greetings, etc. — needed zero changes.
- **Phone number** — required
- **Photo** — optional upload, shown as a circular avatar on both the
  index list and the edit page, with initials as the fallback (matching
  the pattern already used for the topbar avatar)

### Optional staff details (all nullable, none required)

Date of birth, gender, address, job title, date joined, employment type
(full-time/part-time/contract/intern), emergency contact name and phone —
tucked into a collapsed "Additional details (optional)" section on the
form so the common case (create a staff account quickly) isn't cluttered
by fields most people won't fill in immediately.

### Files

```
database/migrations/2026_01_10_000001_add_staff_profile_fields_to_users_table.php
app/Models/User.php      (staff_id generation, name sync, photo_url accessor)
app/Models/Setting.php   (logo_url bugfix)
app/Http/Controllers/Web/UserController.php   (photo upload, all new fields)
resources/views/users/form.blade.php   (split name, phone, photo, optional-details <details>)
resources/views/users/index.blade.php  (photo thumbnail + staff ID)
```

### To apply locally

```powershell
php artisan migrate
php artisan storage:link
```

## Increment 17 — Location Nested Menu + Country/State/City + Visual Polish Pass

### Location grouped under one nested submenu

All location-related screens (Countries, States/Provinces, Cities,
Regions, Hubs & Branches, Outlets, Zones) now sit inside a single
**Location** submenu nested inside Setups, instead of seven flat items
cluttering the list. Setups top level is now: Company Settings → Location
(nested) → Units → Rate Cards → Client Billing → Scan Statuses → Roles &
Permissions → Staff Users. Both the outer Setups group and the inner
Location group auto-expand when the active page is inside them, same
pattern as before.

### Country → State → City (the "operating countries/states/cities" setup)

A real geography hierarchy, additive to (not replacing) Region:

- `countries` (name, ISO code)
- `states` (belongs to a country; name, optional code)
- `cities` (belongs to a state)
- `hubs.city_id` (nullable) — the actual operating location. **Region**
  stays what it was: an access-scoping grouping
  (Global > Region > Hub > Outlet). **City** is "where is this place,
  physically." A hub can — and typically should — have both, for
  different reasons.

This is what actually ties a unit/user to a real place: a user's access
scope resolves to a hub (or region, or outlet); that hub now optionally
carries a city, which carries a state, which carries a country. The Hub
form's City field groups options by "State, Country" so the picker stays
readable without needing full cascading-select JavaScript.

New screens: `/countries`, `/states`, `/cities` — same CRUD pattern as
Regions/Hubs/Outlets, same `locations:*` permission gate.

### Visual polish pass

Applied consistently across every list/form view, not just the new ones:

- **Buttons** — primary "+Add" actions now carry `shadow-sm` and lift to
  `shadow-md` on hover, instead of a flat opacity change only
- **Tables** — every row now has subtle zebra striping
  (`odd:bg-surface-0 even:bg-surface-50/50`) plus a brand-tinted hover
  (`hover:bg-[var(--brand-primary)]/5`) instead of a flat gray hover —
  ties the interaction color back to the deployment's brand color
- **Danger links** ("Remove"/"Delete") — fade on hover instead of a plain
  underline, consistent everywhere
- **Dashboard stat cards** — the `accent` field on each card was defined
  back in Increment 4 but never actually used. Now each card gets a
  colored left border and matching number color (brand/blue/green/red)
  instead of every card looking identical regardless of what it means

### Files

```
database/migrations/2026_01_11_000001_create_countries_table.php
database/migrations/2026_01_11_000002_create_states_table.php
database/migrations/2026_01_11_000003_create_cities_table.php
database/migrations/2026_01_11_000004_add_city_id_to_hubs_table.php
app/Models/Country.php, State.php, City.php
app/Models/Hub.php   (city() relation)
app/Http/Controllers/Web/CountryController.php, StateController.php, CityController.php
app/Http/Controllers/Web/HubController.php   (city_id support)
resources/views/countries/, states/, cities/   (index + form each)
resources/views/hubs/form.blade.php, hubs/index.blade.php   (city field/column)
resources/views/components/layouts/app.blade.php   (nested Location submenu)
resources/views/dashboard/index.blade.php   (accent colors actually applied)
+ repo-wide button/table/link visual upgrades across every existing list view
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 18 — Location Data Seeded, Cities Wired into Shipments, Required-Field Markers

### Country/State/City population

`LocationSeeder` (called from `DatabaseSeeder`) populates:
- **All ~195 world countries** (name + ISO2 code) — a fixed, small
  reference set, seeded in full
- **Nigeria's 36 states + FCT**, each with its capital plus one or two
  other major commercial cities (~80 cities total)

Deliberately **not** attempting every city for every country — that's
hundreds of thousands of rows and not a reasonable thing to hand-seed.
Nigeria is seeded in full detail since it's this deployment's home
operating country; staff add states/cities for any other country they
expand into from Setups → Location → Countries/States/Cities, which
already supports it.

```powershell
php artisan db:seed --class=LocationSeeder
```

### Cities wired into shipment origin/destination

`shipments.origin_city_id` / `destination_city_id` (nullable, additive to
the existing `origin_zone_id`/`destination_zone_id`) — accepted now by:
- `ClientController::quote()`
- `ClientShipmentController::store()` (client portal + external
  integration)
- `ShipmentController::store()` (staff walk-in booking, API)

**Important distinction kept intentional:** Zone stays the actual
rate-calculation key for the `zone_to_zone` billing model — that
architecture doesn't change. City is what a client-facing quote/booking
screen actually lets someone *pick*, since "choose a city" is far more
usable than "choose a zone" for someone who has no concept of your
internal zone map. The shipment list and detail views now show the city
name first, falling back to zone, then the raw address string, wherever
origin/destination is displayed.

### Required-field markers

A small `<x-required />` component (red asterisk, tooltip "Required")
now marks every field that's actually `required` in its controller's
validation rules — applied precisely, not decoratively, across: staff
users, hubs, regions, outlets, units, countries, states, cities, rate
cards, settings, login, and the scan-status quick-add form. Optional
fields (region/city on a hub, invoice header/footer, notes, etc.) are
deliberately left unmarked.

### Unit + operating location, made visible together

The staff user form already had Unit and access-scope (Global/Region/
Hub/Outlet) selection from Increments 15–16. Added: a read-only
**"Operating location"** panel right below the Unit field, on the edit
page, showing the resolved City/State/Country from the person's hub (or
their outlet's parent hub) — e.g. "Ikeja, Lagos, Nigeria" — sourced
directly from the new Country/State/City data. This is what actually
connects "which unit/hub someone belongs to" to "what real place that
is," without duplicating a separate location picker on the user record
itself (the hub's city is the single source of truth).

### Files

```
database/seeders/LocationSeeder.php   (called from DatabaseSeeder)
database/migrations/2026_01_12_000001_add_city_ids_to_shipments_table.php
app/Models/Shipment.php   (origin_city_id/destination_city_id, relations)
app/Http/Controllers/Api/ClientController.php, ClientShipmentController.php, ShipmentController.php
resources/views/components/required.blade.php
resources/views/shipments/index.blade.php, shipments/show.blade.php   (city display fallback chain)
resources/views/users/form.blade.php   (Operating location panel)
+ required-field markers across users, hubs, regions, outlets, units, countries,
  states, cities, rate-cards, settings, login, scan-statuses forms
```

### To apply locally

```powershell
php artisan migrate
php artisan db:seed --class=LocationSeeder
```

## Increment 19 — Hierarchical Codes, Districts/Areas, Filterable Location Setup

### Auto-composed hierarchical codes

Every level below Country now auto-composes its own client-API-facing
`code` from its parent's code — staff only ever type a short suffix:

- State: staff types `short_code` (e.g. "LA"); `code` becomes
  `{country.code}-{short_code}` → **"NG-LA"**
- City: staff types `short_code` (e.g. "IKJ"); `code` becomes
  `{state.code}-{short_code}` → **"NG-LA-IKJ"**
- District: staff types `short_code` (e.g. "GRA"); `code` becomes
  `{city.code}-{short_code}` → **"NG-LA-IKJ-GRA"**

Each model recomputes `code` in a `saving` hook, so it never drifts if
the parent changes later. `short_code` only needs to be unique **within
its parent** (e.g. two different countries can both have a state with
short_code "LA" without colliding), enforced via scoped `Rule::unique()`
in each controller — not a blanket global-uniqueness rule, since that
would be the wrong constraint.

**Client API calls should reference `code`, not `short_code`** —
`short_code` is a data-entry convenience, `code` is the stable identifier.

### Districts/Areas — a new level under City

Completes the hierarchy: **Country > State > City > District/Area**. Same
auto-composed-code pattern, same CRUD pattern as State/City. New
`/districts` screen added to Setups → Location, right after Cities.

### Filterable location setup

`/states` now filters by Country; `/cities` filters by State (or
Country, which narrows to every state in it); `/districts` filters by
State or City — each via a simple `<select onchange="submit">` dropdown,
consistent with the shipment list's existing filter pattern. All three
index pages also now show the composed `code` column.

### Seeded data updated

`LocationSeeder` now assigns a hand-checked-unique `short_code` to every
seeded Nigerian state (2-letter) and city (3-letter, unique within its
state) — without this, the seeded data would have had no `code` at all,
since codes only compose when a `short_code` is present.

### Files

```
database/migrations/2026_01_13_000001_add_short_code_to_states_table.php
database/migrations/2026_01_13_000002_add_codes_to_cities_table.php
database/migrations/2026_01_13_000003_create_districts_table.php
app/Models/State.php, City.php   (auto-compose code on saving)
app/Models/District.php
app/Http/Controllers/Web/StateController.php, CityController.php   (short_code, scoped uniqueness, filters)
app/Http/Controllers/Web/DistrictController.php
resources/views/states/, cities/   (filter dropdown + code column + short_code field)
resources/views/districts/   (index + form)
database/seeders/LocationSeeder.php   (short codes added)
```

### To apply locally

```powershell
php artisan migrate
php artisan db:seed --class=LocationSeeder
```

Re-running `LocationSeeder` is safe (`firstOrCreate` throughout) and will
backfill `short_code`/`code` on any existing seeded rows that predate
this increment.

## Increment 20 — Fixed: Empty-Select Foreign Keys Breaking Saves

### The confirmed bug

Every "blank" option in a `<select>` — "No unit", "No region", "No city
set", "— None —" — submits as an **empty string**, not `null`.
`Validator::validated()` passes that empty string straight through
unchanged. Inserting `''` into a nullable foreign-key column fails the
FK constraint (or an enum-type check, for `employment_type`), which
**silently breaks the entire form save** — not just the one field. Since
"No unit" / "No region" is the default state for most staff and hub
records, this was blocking saves broadly, which is what surfaced as
"can't attach user to unit or location."

Fixed in three controllers — every place with a blank-option select that
wasn't already protected:

- `UserController` — `unit_id`, and defensively `region_id`/`hub_id`/
  `outlet_id`/`employment_type` too (the first three were already safe
  via the access-scope ternary, but explicit is better than relying on
  that alone)
- `HubController` — `region_id`, `city_id`
- `ZoneController` — `hub_id`

Each now normalizes `''` → `null` right after validation, before the
data ever reaches a query.

### On the permission/role issue

I re-read `RolePermissionSeeder`, the guard/permission chain, and
`bootstrap/app.php` line by line and can't find a code-level bug through
static review — it's internally consistent (both guards seeded, roles
assigned to both, no alias conflicts). Rather than ship another
unverified guess after the sanctum-guard fix didn't fully resolve it
last time, I need exact diagnostic output. **Please run this in Tinker
and share what comes back:**

```powershell
php artisan tinker
```
```php
$u = \App\Models\User::where('email', 'test@example.com')->first();
$u->getRoleNames();
$u->getAllPermissions()->pluck('name');
\Spatie\Permission\Models\Permission::where('name', 'roles:read')->get(['name', 'guard_name']);
```

And separately: what exactly happens when you try to open Roles &
Permissions — a 403 page, a blank page, a 500 error, something else?
The exact wording or a screenshot description will tell me precisely
where it's failing instead of me guessing again.

### Files

```
app/Http/Controllers/Web/UserController.php
app/Http/Controllers/Web/HubController.php
app/Http/Controllers/Web/ZoneController.php
```

No migration needed — this is a validation-handling fix only.

## Increment 21 — Title Field + Gender as a Controlled List

- New `title` field (Mr/Mrs/Miss/Ms/Dr/Chief/Engr/Prof/Rev/Alhaji/Alhaja)
  — a dropdown, shown before First/Last name, used for formal address
- `gender` — converted from free text to a fixed list (Male/Female/
  Prefer not to say), both in validation and the form
- Users index now prefixes the title on the name when set (e.g. "Mrs
  Adaeze Okoro")

### Files

```
database/migrations/2026_01_14_000001_add_title_to_users_table.php
app/Models/User.php
app/Http/Controllers/Web/UserController.php
resources/views/users/form.blade.php, users/index.blade.php
```

```powershell
php artisan migrate
```

## Increment 22 — Hub Operational Coverage (States) + Hub-Coded Waybill Numbers

### Hubs can now cover more than one state

`hub_state` pivot table — a hub declares every state it actually picks up
from and delivers to, separate from `city_id` (its single home location,
Increment 17). A hub is very often broader than its home city's state —
this is exactly that. Managed as a checklist (grouped by country) on the
Hub form; shown as a count on the Hubs index.

```php
$hub->states;      // every state this hub operationally covers
$state->hubs;       // reverse: every hub covering this state
```

### Waybill numbers are now coded with the originating hub

`shipments.origin_hub_id` — distinct from `current_hub_id` (which tracks
where the shipment *is right now* and moves as it travels the network).
`origin_hub_id` is fixed at booking time: whichever hub picked up /
originated the shipment, and the tracking number is generated from
**that** hub's code — e.g. a shipment booked through hub `LOS-01` gets a
tracking number starting `LOS01...` instead of the generic `LM...`
prefix used before.

**Resolution order**, handled automatically in `Shipment::booted()`:
1. `origin_hub_id` explicitly provided (client or staff picked a specific
   hub at booking)
2. A hub whose home city (`city_id`) matches the shipment's
   `origin_city_id`
3. Any hub that operationally covers the origin city's **state** (the
   new `hub_state` coverage from above)
4. None found — falls back to the old generic `LM` prefix, so nothing
   breaks for shipments with no city/hub information at all

`origin_hub_id` can also be set explicitly by whoever's booking
(`ClientShipmentController::store`, staff `ShipmentController::store`)
if they already know which hub is handling pickup — the automatic
resolution above only kicks in when it's left blank.

The shipment detail page now shows "Originated at {hub name} ({hub
code})" right under the tracking number.

### Files

```
database/migrations/2026_01_15_000001_create_hub_state_table.php
database/migrations/2026_01_15_000002_add_origin_hub_id_to_shipments_table.php
app/Models/Hub.php   (states() relation)
app/Models/State.php  (hubs() reverse relation)
app/Models/Shipment.php   (origin_hub_id, resolveOriginHub(), hub-coded tracking number)
app/Http/Controllers/Api/ClientShipmentController.php, ShipmentController.php   (origin_hub_id accepted)
app/Http/Controllers/Web/HubController.php   (states checklist sync)
resources/views/hubs/form.blade.php, hubs/index.blade.php   (operating-states checklist/count)
resources/views/shipments/show.blade.php   (originating hub display)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 23 — Waybill Code Shows Both Origin and Destination Hubs

Extends Increment 22: the tracking number now composes from **both**
hub codes, not just the origin — e.g. a shipment from hub `LOS` to hub
`PHC` gets a tracking number starting `LOS-PHC-...` instead of just
`LOS-...`.

`destination_hub_id` mirrors `origin_hub_id` exactly — resolved in the
same order (explicit choice → home-city match → state coverage → none
found), fixed at booking, and never changes as the shipment physically
moves through the network (`current_hub_id`/`current_outlet_id` still
do that job).

If only one side resolves (e.g. destination has no hub coverage yet),
the tracking number falls back to just that one hub's code, same as
before this increment. If neither resolves, it falls back to the
original generic `LM` prefix.

The shipment detail page now shows `{origin hub} ({code}) → {destination
hub} ({code})` when both are known.

### Files

```
database/migrations/2026_01_16_000001_add_destination_hub_id_to_shipments_table.php
app/Models/Shipment.php   (destination_hub_id, composeTrackingNumber() now takes both hubs)
app/Http/Controllers/Api/ClientShipmentController.php, ShipmentController.php   (destination_hub_id accepted)
app/Http/Controllers/Web/ShipmentController.php   (eager-loads destinationHub)
resources/views/shipments/show.blade.php   (shows both hub codes)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 24 — City-Level Operational Hub Override (Resolves Multi-Hub-Per-State Ambiguity)

`Hub::states()` (Increment 22) allows more than one hub to cover the same
state — correct for coverage, but ambiguous the moment `Shipment` needs
to resolve exactly ONE hub for a city and that city's state has multiple
covering hubs. Previously it just took whichever hub came first.

`cities.operational_hub_id` (nullable) fixes that: an explicit,
optional pin — "this specific city is handled by THIS hub," regardless
of how many hubs cover its state. `Shipment::resolveHubForCity()` now
checks this first, before the home-city match, before the
(now-disambiguated) state-coverage fallback.

Only needed where the ambiguity actually exists — leave it unset for any
city whose state has just one covering hub.

### Files

```
database/migrations/2026_01_17_000001_add_operational_hub_id_to_cities_table.php
app/Models/City.php   (operationalHub() relation)
app/Models/Shipment.php   (resolveHubForCity() checks the override first)
app/Http/Controllers/Web/CityController.php   (operational_hub_id, empty-string normalized from the start)
resources/views/cities/form.blade.php, cities/index.blade.php   (field + column)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 25 — Clarified: Operational Hub Has No State Restriction

Confirms and clarifies what Increment 24 already did, since it wasn't
obvious from the UI alone: **every** city can have an operational hub
set, whether or not there's an actual multi-hub conflict on its state,
and the hub picked can be based in **any** state — nothing ties the
override to the city's own state or region. `resolveHubForCity()` was
already unconstrained; this increment just makes that visible:

- The hub dropdown on the City form now shows each hub's own home city
  and state (e.g. "Lagos Hub (LOS-01) — Ikeja, Lagos"), so picking one
  from a different state is a clear, deliberate choice rather than a
  guess
- Updated the field's help text to say this outright, rather than
  implying it's "only" for resolving conflicts

No functional/model change — `Shipment::resolveHubForCity()` already
worked exactly this way in Increment 24; this is a UI-clarity pass only.

### Files

```
app/Http/Controllers/Web/CityController.php   (eager-loads hub's city/state for the dropdown)
resources/views/cities/form.blade.php   (dropdown shows hub location, clarified help text)
```

No migration needed.

## Increment 26 — Onforwarding Classification (Billing Module)

Cities and Districts can now carry an **Onforwarding Classification** —
a billing concept for locations outside the direct hub network that need
handing off to a third party/local courier to complete delivery,
typically at an extra charge.

### Why a separate lookup table, not a boolean

Kept as its own small table (`onforwarding_classifications`: name,
surcharge_amount, is_default) rather than a flag on City/District, so
more than one tier can exist at different fee levels (e.g.
"Onforwarding - Near" vs "Onforwarding - Remote"), and so the fee amount
lives in one place instead of being duplicated per location. Managed
under **Setups → Client Billing → Onforwarding Classifications**, since
this is fundamentally a billing configuration, not a location one.

### How it applies

Every city — and, more specifically, every district — can have a
classification assigned, whether or not there's an actual reason to
(same "no restriction" principle as the operational hub override in
Increment 24/25). When set, `ShipmentPricingService::calculateOnforwarding()`
checks **both sides of the shipment independently**:

- If a district is specified (new `origin_district_id`/
  `destination_district_id` on shipments, mirroring the city fields),
  its classification takes priority — being the more specific match
- Otherwise falls back to the city's own classification
- Origin and destination are summed separately, so a shipment
  onforwarding on *both* ends is charged for both

Treated like insurance in the pricing waterfall: **not** discounted (it's
a pass-through cost, not part of the negotiated rate), but **is** subject
to VAT. Recorded as its own `onforwarding_amount` line on the shipment,
shown separately in the billing breakdown rather than folded into
"surcharges."

### Files

```
database/migrations/2026_01_18_000001_create_onforwarding_classifications_table.php
database/migrations/2026_01_18_000002_add_onforwarding_classification_to_cities_and_districts_table.php
database/migrations/2026_01_18_000003_add_district_and_onforwarding_to_shipments_table.php
app/Models/OnforwardingClassification.php
app/Models/City.php, District.php   (onforwardingClassification() relation)
app/Models/Shipment.php   (origin_district_id/destination_district_id, onforwarding_amount)
app/Services/ShipmentPricingService.php   (calculateOnforwarding())
app/Http/Controllers/Web/OnforwardingClassificationController.php
app/Http/Controllers/Web/CityController.php, DistrictController.php   (classification field)
app/Http/Controllers/Api/ClientController.php, ClientShipmentController.php, ShipmentController.php   (district fields accepted)
resources/views/onforwarding-classifications/   (index + form)
resources/views/cities/, districts/   (classification field/column)
resources/views/shipments/show.blade.php   (onforwarding line in billing breakdown)
```

### Known gap (pre-existing, not new)

Staff walk-in bookings (`ShipmentController::store`, API) still don't run
through `ShipmentPricingService` at all — same gap flagged since
Increment 10 — so `onforwarding_amount` (like every other pricing field)
stays at 0 for those until that's wired up.

### To apply locally

```powershell
php artisan migrate
```

## Increment 27 — Postal Codes for State, City, District

Plain optional `postal_code` field added to all three — unlike `code`
(the auto-composed hierarchical identifier from Increment 19),
`postal_code` is a real-world value staff type directly, since actual
postal/zip systems don't compose from a parent the way this app's
internal codes do.

Shown as a column on each of the three list pages, editable on each
form. No validation beyond a sensible length cap — postal code formats
vary too widely across countries to usefully constrain further.

### Files

```
database/migrations/2026_01_19_000001_add_postal_code_to_states_cities_districts_table.php
app/Models/State.php, City.php, District.php
app/Http/Controllers/Web/StateController.php, CityController.php, DistrictController.php
resources/views/states/, cities/, districts/   (form field + index column, all three)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 28 — Billing as a Top-Level Module + Units Moved to Location

### Nav restructure

**Billing** is now its own top-level sidebar group, parallel to Setups —
not nested inside it, since billing configuration (rates, zone pricing,
onforwarding, invoicing, client discounts) is distinct enough to warrant
its own primary group. Contains:

- Zones
- Rate Cards
- Onforwarding
- Zone Mapping (new — see below)
- Invoice (new — see below)
- Client Billing

**Units** moved from a flat item under Setups into the **Location**
submenu, alongside Countries/States/Cities/Districts/Regions/Hubs/
Outlets — it's organizational structure within a hub, the same
conceptual area as the rest of Location.

Note: this is a **visual/navigation change only** — none of the
underlying route permissions changed. Zones is still gated to
`locations:read`, Rate Cards to `rates:read`, etc. Moving a screen in the
sidebar doesn't change who can already reach it.

### Zone Mapping — new screen

Surfaces `ZoneRateMatrix` (the zone-to-zone price pairs from Increment 2)
in its own centralized view, filterable by rate card, instead of only
being reachable from inside one specific zone-to-zone rate card's edit
page. Same underlying upsert/delete as
`RateCardController::setZonePrice()`/`destroyZonePrice()` — this is a
second entry point into the same operation, not a duplicate
implementation.

### Invoice — new screen

A staff-facing billing statement across every client's shipments —
filterable by portal client, API integration, or date range. Same design
decision as `Api\ClientController::invoices()` (Increment 10): **there's
no separate invoice-document entity in this system** — each shipment's
own billing breakdown (base, onforwarding, VAT, total) is the invoice.
This is that same statement view, just staff-facing across everyone
instead of scoped to one client.

Added `Shipment::clientUser()` / `Shipment::apiClient()` relations (were
missing before — the columns existed since early on, but nothing had
needed the relation itself until now).

### Files

```
app/Http/Controllers/Web/ZoneMappingController.php
app/Http/Controllers/Web/InvoiceController.php
app/Models/Shipment.php   (clientUser(), apiClient() relations)
resources/views/zone-mappings/index.blade.php
resources/views/invoices/index.blade.php
resources/views/components/layouts/app.blade.php   (Billing top-level group, Units moved)
routes/web.php   (zone-mappings.*, invoices.index routes)
```

No migration needed — this increment is entirely nav/screens, no schema
changes.

## Increment 29 — Billing Moved Back Under Setups

Reverses the "top-level module" part of Increment 28 — Billing is now a
nested submenu **inside** Setups, right alongside Location, using the
exact same collapsible pattern (its own `<details>`, same indent level,
same auto-expand-when-active behavior). The screens themselves (Zones,
Rate Cards, Onforwarding, Zone Mapping, Invoice, Client Billing) and
their permissions are unchanged — only where the group sits in the
sidebar.

The topbar eyebrow label simplified back to just "Setups" for any page
inside Billing or Location, rather than a separate "Billing" label — same
as how Location pages already behaved.

### Files

```
resources/views/components/layouts/app.blade.php
```

No migration, no other file changes.

## Increment 30 — Standard Courier Zone-Tier Model (A–F + International)

Formalizes zones against the industry-standard tier structure:

| Tier | Coverage | Billing Purpose |
|---|---|---|
| A | Same city / Local delivery | Lowest tariff |
| B | Nearby towns within the same state | Short-distance tariff |
| C | Neighboring states | Medium-distance tariff |
| D | Regional destinations | Higher tariff |
| E | Long-distance/interstate | Premium tariff |
| F | Remote or hard-to-reach areas | Highest tariff, possible surcharge |
| International | Countries grouped by region (West Africa, Europe, North America, Asia, etc.) | International tariffs |

### Important distinction

A zone's **tier** classifies *what kind* of coverage it represents —
it's descriptive/organizational. The actual **price** between any two
zones still lives entirely in `ZoneRateMatrix`, managed under
**Billing → Zone Mapping** (Increment 28) — this increment doesn't
change how pricing works, it adds a standard vocabulary on top of the
zones that pricing already applies to.

- `zones.tier` (nullable enum: A–F, international)
- `zones.coverage_description` (nullable, free text) — auto-suggested
  from the tier's standard description when left blank (both
  server-side as a fallback, and client-side via a small script so
  staff see it fill in immediately), always overridable
- `Zone::TIERS` — the reference table (label, standard coverage
  description, billing purpose) living on the model, used by the tier
  picker and the index badge's tooltip
- Zones index shows the tier as a badge; hovering shows its billing
  purpose

### Files

```
database/migrations/2026_01_20_000001_add_tier_to_zones_table.php
app/Models/Zone.php   (TIERS constant, tierLabel()/tierPurpose())
app/Http/Controllers/Web/ZoneController.php   (tier + coverage_description validation)
resources/views/zones/form.blade.php   (tier picker, auto-suggested description)
resources/views/zones/index.blade.php   (tier badge column)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 31 — Simplified Zone Creation: Domestic/International + Coverage Description

Simplifies what's actually required when creating a zone, per feedback
that the full tier picker (Increment 30) was more than needed up front:

- **Type** (`domestic` / `international`) — now the required, primary
  classification. Shown as two clear options, domestic selected by
  default.
- **Coverage description** — now required too (e.g. "Nearby towns within
  the same state" for a domestic zone, or "West Africa" for an
  international one)
- **Tier (A–F)** — demoted to an optional refinement, and now **only
  shown/relevant for domestic zones** — the field hides itself via a
  small script when International is selected, and the controller clears
  `tier` server-side regardless of what was posted if type is
  international (doesn't rely on the UI hiding alone). This matches the
  original table directly: A–F are domestic tariff tiers; international
  zones are grouped by region instead, which has no equivalent tier.

`Zone::TIERS` no longer includes an `international` entry — that's now
`Zone::TYPES`, a separate, simpler two-value reference table.

### Files

```
database/migrations/2026_01_21_000001_add_type_to_zones_table.php
app/Models/Zone.php   (TYPES constant, tier removed from TIERS, typeLabel())
app/Http/Controllers/Web/ZoneController.php   (type required, tier cleared for international)
resources/views/zones/form.blade.php   (Type radio first, Tier hidden for international)
resources/views/zones/index.blade.php   (Type column added)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 32 — Zone Redefined as a Relationship Classifier + Three New Billing Models

Significant redefinition, based directly on your clarification of what
"Zone" and "Zone Mapping" actually mean.

### Assumption made — please confirm

**"Zone Mapping" now means: each city is assigned to exactly one Zone**
(e.g. "Port Harcourt = Zone 2"), not a direct city-PAIR-to-zone lookup.
This is what lets a rate table with "From Zone"/"To Zone" columns
resolve pricing for *any* route between two mapped cities automatically
— you only assign each city once, not every possible pair. If you
actually meant a direct pair mapping ("Lagos→PHC" as one specific
route, distinct from "Lagos→Enugu" even though both might go through
PHC's zone), tell me and I'll adjust — that's a materially different
(and more tedious to maintain) design.

### `zone_mappings` — city → zone assignment

New screen at **Billing → Zone Mapping** (repurposed from what it
briefly was in Increment 28 — a shortcut into the old zone-to-zone price
matrix. That matrix still exists and still works exactly as before, just
managed from each `zone_to_zone` rate card's own edit page again, the
way it was before Increment 28 added the shortcut). Assign a city to a
zone; re-assigning updates rather than duplicating.

### `zone_weight_rates` — the origin-destination + weight rate table

Backs the new **origin_destination_weight** billing model, matching
exactly the table you described:

> From Zone | To Zone | Min weight | Max weight | Service Type | Price | Transit days | Extra amount per extra kg

Managed on the rate card's own edit page (same pattern as the
zone-to-zone matrix). At quote time: the shipment's origin/destination
cities resolve to zones via `zone_mappings`, then the matching row for
(from zone, to zone, service type, weight band) gives the price. Weight
beyond a matched row's `max_weight` is charged at that row's
`extra_amount_per_extra_kg`. If the shipment is heavier than every band
defined for that zone pair/service, the highest band is used as the
base with the overage still applied — so a shipment never fails to
price just for being heavier than anticipated.

One simplification from your spec: your table listed both "extra-kg"
and "extra-amount-per-extra-kg" as separate columns — I've treated these
as one field (the per-kg overage rate), since a separate "extra-kg"
value didn't have a distinct role I could resolve confidently. Flag it
if you meant something more specific there (e.g. a rounding increment
size).

### `truckload` and `carton_rate` — new flat-rate-per-unit models

Both are `quantity × rate` — identical mechanically, just naming the
unit differently. `shipments.quantity` (nullable integer) holds "number
of truckloads" or "number of cartons" depending on which billing model
applies; accepted now by every booking/quote endpoint.

### `rate_cards.billing_model` converted from enum to string

Was a fixed 9-value DB enum; adding 3 more this way isn't sustainable.
Converted to a plain string with the allowed list enforced by
`RateCardController`'s validation instead (same pattern as `Zone::TYPES`
elsewhere in this app). **Requires `doctrine/dbal`** for the migration's
`->change()` call:

```powershell
composer require doctrine/dbal
```

### Files

```
database/migrations/2026_01_22_000001_convert_rate_cards_billing_model_to_string.php
database/migrations/2026_01_22_000002_create_zone_mappings_table.php
database/migrations/2026_01_22_000003_create_zone_weight_rates_table.php
database/migrations/2026_01_22_000004_add_quantity_to_shipments_table.php
app/Models/ZoneMapping.php, ZoneWeightRate.php
app/Models/Zone.php, City.php   (zoneMapping(s) relations)
app/Models/Shipment.php   (quantity)
app/Services/RateEngine.php   (originDestinationWeight(), perUnit() for truckload/carton_rate)
app/Http/Controllers/Web/RateCardController.php   (3 new models, weight-rate table CRUD)
app/Http/Controllers/Web/ZoneMappingController.php   (rewritten: city->zone, not zone-pair pricing)
app/Http/Controllers/Api/ClientController.php, ClientShipmentController.php, ShipmentController.php   (quantity accepted)
resources/views/rate-cards/form.blade.php   (new config fields + rate-table editor)
resources/views/zone-mappings/index.blade.php   (rewritten for city assignment)
routes/web.php   (weight-rates routes)
```

### To apply locally

```powershell
composer require doctrine/dbal
php artisan migrate
```

## Increment 33 — Correction: Zone Mapping Is a State-Pair → Zone Lookup

Corrects Increment 32's Zone Mapping design after clarification: a route
between two **states** — regardless of direction — equates to **one**
zone. "Abuja to Lagos = Zone 2" and "Lagos to Abuja" are the same
mapping, not two, and not a per-city assignment.

### What changed

- `zone_mappings`: dropped and recreated as `state_a_id` / `state_b_id` /
  `zone_id` (unique on the pair), replacing the previous single-city
  `city_id → zone_id` design
- `ZoneMapping::booted()` normalizes `state_a_id`/`state_b_id` to always
  store the lower ID first, so one row covers the route both ways —
  `ZoneMapping::resolveZone($stateOneId, $stateTwoId)` normalizes the
  same way on lookup, regardless of which order the two IDs are passed in
- `zone_weight_rates`: `from_zone_id`/`to_zone_id` replaced with a single
  `zone_id` — since a route now resolves to exactly one zone, pricing
  only needs one zone dimension too, not a matrix
- `RateEngine::resolveZoneIdForRoute()` (renamed from `resolveZoneId`)
  now resolves each city to its **state** first, then calls
  `ZoneMapping::resolveZone()` — zone mapping operates at the state
  level, not the city level
- Rate Card's weight-rate table, the Zone Mapping screen, and every
  related controller updated to match — "From Zone"/"To Zone" columns
  are gone everywhere, replaced with a single "Zone" column

No production data depended on the old structure (it was one increment
old), so this drops and recreates the affected tables rather than
carrying forward a design that didn't match the actual requirement.

### Files

```
database/migrations/2026_01_23_000001_recreate_zone_mappings_as_state_pairs.php
database/migrations/2026_01_23_000002_convert_zone_weight_rates_to_single_zone.php
app/Models/ZoneMapping.php   (state_a_id/state_b_id, normalization, resolveZone())
app/Models/ZoneWeightRate.php   (single zone() relation)
app/Models/Zone.php, City.php   (stale references cleaned up)
app/Services/RateEngine.php   (resolveZoneIdForRoute via state, not city)
app/Http/Controllers/Web/ZoneMappingController.php   (state_a/state_b form handling)
app/Http/Controllers/Web/RateCardController.php   (single zone_id validation)
resources/views/zone-mappings/index.blade.php   (State A / State B pickers)
resources/views/rate-cards/form.blade.php   (single Zone column throughout)
```

### To apply locally

```powershell
php artisan migrate
```

Since this drops and recreates `zone_mappings`, any test data you
already entered under the old (incorrect) city-based design will be
lost — re-enter it as state pairs after migrating.

## Increment 34 — Responsive Layout Across Every Page + Logistics-Themed Login

### Responsive sidebar (benefits every page at once)

The sidebar was a fixed-width static column with no mobile behavior at
all — unusable on a phone. Since every screen in the app shares
`components/layouts/app.blade.php`, fixing it there fixes it everywhere:

- Sidebar is now an off-canvas drawer below the `md` breakpoint (hidden
  by `-translate-x-full`, slides in via a hamburger button in the
  topbar), and the same static column as before at `md` and up
- A semi-transparent backdrop appears behind the open drawer on mobile;
  tapping it (or the new close button in the drawer itself) closes it
- Topbar padding/spacing tightens on mobile (`px-4` vs `px-8`,
  `p-4` vs `p-8` on the main content area), and the signed-in user's name
  hides on the smallest screens (the avatar initial alone is enough
  there)
- No new dependency — a dozen lines of plain JS toggling classes

### Every table now scrolls horizontally on small screens

Every list page's table wrapper (18 files) changed from `overflow-hidden`
to `overflow-x-auto` — on mobile, wide tables (Shipments, Users, Rate
Cards, etc.) now scroll sideways within their card instead of squashing
illegibly or breaking the page layout. Rounded corners are unaffected —
`overflow-x-auto` still clips to the border-radius the same way
`overflow-hidden` did.

### Login page — properly logistics-themed

Replaced the plain centered card with a split-panel design:

- **Left panel** (desktop only — no room for it on mobile): brand-primary
  background with an actual illustration — two map pins joined by a
  dashed route, a truck icon animating gently back and forth along it,
  and a few translucent floating package icons for texture. A short
  tagline underneath.
- **Right panel**: the sign-in form itself, unchanged in function, with a
  slightly refined button (shadow lift on hover, matching the rest of the
  app's button style from Increment 17)
- On mobile, the left panel disappears entirely and the form takes the
  full width with a small centered logo header instead — the same
  fallback behavior as before

New icons added to the shared icon component: `truck`, `map-pin`,
`route`, `package`, `menu` (hamburger), `close`.

### Files

```
resources/views/components/icon.blade.php   (6 new icons)
resources/views/components/layouts/app.blade.php   (responsive drawer, hamburger toggle)
resources/views/auth/login.blade.php   (full redesign)
+ overflow-x-auto swapped in on every table wrapper across 18 list-view files
```

No migration, no controller changes — this increment is entirely
front-end.

## Increment 35 — Zone Mapping Screen Trimmed to Just the Essentials

Removed the explanatory paragraphs from the Zone Mapping screen — the
"how this works" description at the top, and the "order doesn't matter"
note above the assignment form. The screen now shows only what's
functional: the zone filter, the State A / State B / Zone table, and the
assignment form itself. No behavior changed, purely a leaner page.

### Files

```
resources/views/zone-mappings/index.blade.php
```

No migration, no controller changes.

## Increment 36 — Auto-Generated Domestic Mapping + Country-Based International Mapping

Removed the zone filter dropdown ("the top part") and replaced manual
one-at-a-time entry with two auto-generated, inline-editable sections.

### Domestic Mapping

"Generate Nigeria combinations" creates every possible state-pair
combination for Nigeria (36 states + FCT → ~666 pairs) in one click,
each starting **unassigned**. Staff then work through the list and pick
a zone per row from an inline dropdown that saves immediately on
change — no separate add/edit form needed anymore, since the full set
of pairs already exists.

Idempotent: safe to click again later (e.g. after adding a new state
under Setups → Location) — it only creates pairs that don't already
exist and never touches a zone already assigned to an existing pair.
`zone_mappings.zone_id` is now nullable to support this (previously
required).

### International Mapping — new, country-based (not a pair)

International works differently on purpose: since the business always
ships **from** Nigeria, an international shipment only needs to know
which zone the *other* country belongs to (e.g. "France = Europe zone")
— Nigeria is always the fixed side, so there's no pair to resolve the
way domestic needs one. New `zone_country_mappings` table: one row per
country (excluding Nigeria itself), same "Generate" + inline-assign
pattern as domestic.

### Files

```
database/migrations/2026_01_24_000001_make_zone_mappings_zone_id_nullable.php
database/migrations/2026_01_24_000002_create_zone_country_mappings_table.php
app/Models/ZoneCountryMapping.php
app/Http/Controllers/Web/ZoneMappingController.php   (rewritten: generate + inline update actions)
resources/views/zone-mappings/index.blade.php   (rewritten: two sections, no filter, inline zone selects)
routes/web.php   (generate-domestic, generate-international, update-zone, update-country-zone)
```

### To apply locally

```powershell
php artisan migrate
```

Note: this requires `doctrine/dbal` to modify the `zone_id` column
(same requirement already introduced in Increment 32 for the
`billing_model` column change) — if you haven't added it yet:
```powershell
composer require doctrine/dbal
```

## Increment 37 — Selectable Login Page Designs + More Color

Four distinct login page illustrations, selectable from **Setups →
Company Settings → Login page design**:

- **Route** — the original design (truck driving a dashed path between
  two map pins)
- **Warehouse** — a warm grid of package icons behind a large highlighted
  package, leaning on the brand secondary color
- **Map** — a dotted map field with several colorful pins (using the
  status-delivered green and status-transit blue for variety, not just
  brand colors) and crossing dashed routes
- **Vibrant** — a genuinely multi-tone gradient (brand primary → purple →
  brand secondary → orange) rather than a flat brand-color background,
  with floating truck/package/pin/route icons — directly answering "more
  colours," since every other design still centers on the brand palette

### How it's wired

- `settings.login_design` (string, default `'route'`) — overlaid onto
  `config('branding.login_design')` the same way every other branding
  setting already works (Increment 5's `BrandingServiceProvider` pattern)
- `Setting::LOGIN_DESIGNS` is the single source of truth for what
  exists — both the settings-page picker and the `@include` on the login
  page read from it, so adding a fifth design later means one new
  `resources/views/auth/designs/{key}.blade.php` partial and one new
  array entry, nothing else
- The login page itself just does
  `@include('auth.designs.' . config('branding.login_design'))` — the
  surrounding layout, right-panel sign-in form, and responsive mobile
  fallback (Increment 34) are shared across all four designs unchanged

### Files

```
database/migrations/2026_01_25_000001_add_login_design_to_settings_table.php
app/Models/Setting.php   (login_design fillable, LOGIN_DESIGNS constant)
app/Providers/BrandingServiceProvider.php   (login_design overlay)
app/Http/Controllers/Web/SettingsController.php   (login_design validation)
resources/views/settings/edit.blade.php   (design picker section)
resources/views/auth/login.blade.php   (dynamic @include)
resources/views/auth/designs/route.blade.php, warehouse.blade.php, map.blade.php, gradient.blade.php
```

### To apply locally

```powershell
php artisan migrate
npm run build
```

## Increment 38 — Territories: Auto-Determined Domestic Zone Tiers + Routes Foundation

### Territories: the rule that auto-fills domestic zone mapping

New `territories` table groups states together purely for the domestic
zone-tier rule — e.g. a "South West" territory containing Lagos, Ogun,
Oyo, Osun, Ondo, Ekiti. Distinct from Region (Increment 13, an
access-scope grouping) — Territory exists only for this rule.

`states` gained `territory_id` (optional — which territory this state
belongs to) and `has_airport` (boolean).

`ZoneMapping::determineDefaultZoneTier(State $a, State $b)` — the rule,
checked in order:

1. **Same state** → Zone 1
2. **Different states, same territory** → Zone 2
3. **Different territories, both states have an airport** → Zone 3
4. **Different territories, at least one state has no airport** → Zone 4

`Zone::ensureDefaultZones()` creates (once, idempotently) the four
standard "Zone 1"–"Zone 4" records this rule assigns into.
`ZoneMappingController::generateDomestic()` (Increment 36) now calls
both when generating Nigeria's state combinations — every newly
generated pair is pre-filled with its rule-determined zone instead of
starting unassigned. **This is only ever a starting point** — any
individual pair can still be reassigned to a different zone afterward
via the existing inline picker, exactly as before; the rule never
touches a pair that's already been assigned, including on repeated runs.

International mapping (Increment 36) is **not** given an equivalent
auto-rule in this increment — there's no clear equivalent to
"territory"/"airport" for countries without more specific direction, so
country → zone assignment stays fully manual via the existing inline
picker. Flag it if a country-grouping concept (e.g. "region") and a
matching auto-rule should exist too.

### Routes: foundation for a future feature, not built yet

New `routes` table (name, code, optional hub) + optional `route_id` on
both `City` and `District`. This is explicitly **only the data model** —
grouping cities/districts into a route for **future** automatic shipment
sorting and driver/rider allocation. No sorting or allocation logic
exists in this increment; that's deferred to whichever future module
actually implements it.

### New screens

- **Territories** (Setups → Location) — simple name/code CRUD
- **Routes** (Setups → Location) — name/code/optional-hub CRUD
- State form gained a Territory picker and a "Has an airport" checkbox
- City and District forms each gained an optional Route picker

### Files

```
database/migrations/2026_01_26_000001_create_territories_table.php
database/migrations/2026_01_26_000002_add_territory_and_airport_to_states_table.php
database/migrations/2026_01_26_000003_create_routes_table.php
app/Models/Territory.php, Route.php
app/Models/State.php   (territory(), has_airport)
app/Models/City.php, District.php   (route())
app/Models/ZoneMapping.php   (determineDefaultZoneTier())
app/Models/Zone.php   (ensureDefaultZones())
app/Http/Controllers/Web/TerritoryController.php, RouteController.php
app/Http/Controllers/Web/StateController.php   (territory_id, has_airport)
app/Http/Controllers/Web/CityController.php, DistrictController.php   (route_id)
app/Http/Controllers/Web/ZoneMappingController.php   (generateDomestic() applies the rule)
resources/views/territories/, routes/   (index + form each)
resources/views/states/form.blade.php, cities/form.blade.php, districts/form.blade.php
resources/views/components/layouts/app.blade.php   (Territories, Routes added to Location submenu)
```

### To apply locally

```powershell
php artisan migrate
```

### A naming note, if you extend this yourself later

The new `Route` model (`App\Models\Route`) sits right next to Laravel's
own `Illuminate\Support\Facades\Route` facade used throughout
`routes/web.php` — they don't actually collide (the facade is never
imported inside `RouteController.php`, and `routes/web.php` never needs
to import the model), but if you ever add code that needs both in the
same file, you'll need an import alias (`use App\Models\Route as
DeliveryRoute;` or similar).

## Increment 39 — CSV Export/Import for the Location & Zone Setup Screens

Added a download-amend-reupload workflow to the screens where it matters
most — the location hierarchy and both zone mapping tables, where the
domestic mapping alone is ~666 rows.

### Covered in this increment

- **Countries** — name, code
- **States** — country_code, name, short_code, territory_code, has_airport, postal_code
- **Cities** — state_code, name, short_code, postal_code
- **Districts** — city_code, name, short_code, postal_code
- **Territories** — name, code
- **Zones** — name, code, type, tier, coverage_description
- **Zone Mapping — Domestic** — state_a_code, state_b_code, zone_code (the highest-value one: ~666 rows, previously only editable one row at a time)
- **Zone Mapping — International** — country_code, zone_code

### Not covered in this increment

Regions, Hubs, Outlets, Units, Routes, Onforwarding Classifications, Rate
Cards, and Client Billing don't have CSV support yet — smaller datasets
where the one-at-a-time forms are less painful, and I wanted to ship the
highest-value screens rather than a thin layer spread across everything.
Flag it if any of these should be added next; the shared `CsvService`
below makes each one a small, consistent addition.

### How it works

- **`app/Services/CsvService.php`** — shared by every screen.
  `download()` streams a CSV; `parse()` reads an uploaded CSV into
  associative rows keyed by its own header row, so column order in the
  uploaded file doesn't matter.
- **Natural keys, not database IDs** — every CSV references rows by
  their human-readable **code** (country code, the composed state/city
  code, territory code, zone code), never a raw ID. This is exactly why
  the auto-composed `code` system (Increment 19) exists: a file is
  portable across environments and safe to hand-edit, since IDs would
  mean nothing to a person and wouldn't survive a re-import into a
  different database.
- **Import always upserts, never blind-inserts** — every import uses
  `updateOrCreate` keyed on the same natural fields the form itself
  treats as unique (e.g. country by code, state by
  country+short_code), so re-uploading an amended export updates
  existing rows in place rather than duplicating them. Rows referencing
  something that doesn't exist (an unknown state/city/country code) are
  skipped and counted, not silently dropped — the status message
  reports both how many imported and how many were skipped.
- **`<x-csv-actions>`** — one shared Blade component (file input +
  Import button, Export link) used identically on every screen.

### Files

```
app/Services/CsvService.php
app/Http/Controllers/Web/CountryController.php, StateController.php, CityController.php, DistrictController.php, TerritoryController.php, ZoneController.php   (export()/import())
app/Http/Controllers/Web/ZoneMappingController.php   (exportDomestic()/importDomestic()/exportInternational()/importInternational())
resources/views/components/csv-actions.blade.php
resources/views/countries/, states/, cities/, districts/, territories/, zones/, zone-mappings/   (index views, CSV bar added)
routes/web.php   (export routes under *:read, import routes under *:update)
```

No migration needed — this increment only adds controller methods,
routes, and view markup.

## Increment 40 — Billing Corrections: Zone Model Clarity, Real Carton Rate, Walk-In Pricing

Three corrections identified in a full system review, tackled together
since billing is foundational to everything built on top of it.

### 1. Zone-to-Zone vs Origin-Destination — clarified, not merged

Both models price a route between two zones, but solve different
problems, and the near-identical old labels made the choice a guess:

- **`zone_to_zone`** — one fixed price per zone pair, full stop
- **`origin_destination_weight`** — a full rate table: price varies by
  weight band and service type, with transit days and an overage rate

Relabeled both, and added live guidance text under the billing-model
picker on the Rate Card form (`RateCardController::ZONE_MODEL_GUIDANCE`)
that shows/hides with the same JS that already toggles the model-specific
fields — so the distinction is explained right where the decision gets
made, not buried in documentation.

### 2. Carton Rate — now actually zone + size aware

Previously `carton_rate` was `quantity × a single flat number` —
ignoring both carton size and zone entirely, despite that being the
explicit original spec ("small carton, big, medium... will use zone too,
with pieces as multiplier"). Corrected:

- New `carton_rates` table: one row per (rate card, zone, carton size),
  same "each rate card owns its own table" pattern as
  `zone_weight_rates`
- `shipments.carton_size` (small/medium/large) — accepted now by every
  booking endpoint alongside the existing `quantity`
- `RateEngine::cartonRate()` resolves the shipment's zone the same way
  `originDestinationWeight()` does (city → state → `ZoneMapping`), looks
  up the matching `(zone, carton_size)` row, multiplies by quantity.
  Degrades to 0 rather than throwing if no matching row exists — a
  missing setup row shouldn't hard-fail a booking
- New "Carton rates" management section on the Rate Card edit page (only
  shown when `carton_rate` is the selected billing model), same
  add/remove pattern as the weight-rate table

### 3. Staff walk-in bookings now price correctly — the oldest open gap, closed

`Api\ShipmentController::store()` previously created a shipment with
**no price at all** — every billing feature built since Increment 10
(special discounts, onforwarding, zone-based rates, now carton rates)
only ever applied to client-portal/API bookings. It now resolves pricing
exactly the way `ClientShipmentController` does: looks up the active
rate card for the service type (or uses an explicitly provided
`rate_card_id`), resolves any billing discount, and calls
`ShipmentPricingService::priceShipment()` before creating the shipment.

`client_user_id` is now accepted (optional — a walk-in customer may not
have a portal account at all). When provided and that client has a
Special billing profile, their discount applies here too — via the new
`ClientBillingProfile::resolveForClientUser()`, since
`resolveForRequest()` would have resolved the **staff member's** own
(nonexistent) billing profile instead of the client's — the requester
and the client are different people in this flow, unlike the client
portal where they're the same.

**Related gap, not fixed here:** there's still no staff-facing Blade
booking form — walk-in booking is API-only right now (confirmed by
checking `resources/views/shipments/`, which only has `index` and
`show`). This endpoint now prices correctly, but nothing in the staff
dashboard actually calls it yet. Flag it if a booking screen should be
built next.

### Files

```
database/migrations/2026_01_27_000001_create_carton_rates_table.php
app/Models/CartonRate.php
app/Models/Shipment.php   (carton_size)
app/Models/ClientBillingProfile.php   (resolveForClientUser())
app/Services/RateEngine.php   (cartonRate(), perUnit() now truckload-only)
app/Http/Controllers/Web/RateCardController.php   (relabeled models, ZONE_MODEL_GUIDANCE, carton-rate CRUD)
app/Http/Controllers/Api/ShipmentController.php   (rewritten: resolves pricing, accepts client_user_id)
app/Http/Controllers/Api/ClientController.php, ClientShipmentController.php   (carton_size accepted)
resources/views/rate-cards/form.blade.php   (guidance text, carton-rates section)
routes/web.php   (carton-rates routes)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 41 — Billing Model Layer Cleared, Rebuilding From a Reference List

Per explicit instruction: the whole billing-model calculation layer is
deleted, to be rebuilt one model at a time — each discussed and
configured deliberately rather than shipping all 12 at once. This
increment does the deletion and lays down step one of the rebuild: a
simple reference list.

### What was deleted

- `rate_cards`, `zone_rate_matrix`, `zone_weight_rates`, `carton_rates`
  tables — dropped, not migrated forward (nothing in this build has ever
  run against real data, so there was nothing to preserve)
- `shipments.rate_card_id` — dropped
- `RateCard`, `ZoneRateMatrix`, `ZoneWeightRate`, `CartonRate` models
- `RateEngine` service (the actual per-model calculation logic)
- `RateCardController` (Web) and its views
- `Api\RateController` — a **dead duplicate** discovered during this
  cleanup: an old API-only rate-card controller from Increment 3, still
  registered in `routes/api.php`, fully superseded by the Web
  `RateCardController` years ago but never removed. Deleted along with
  everything else.

### What was deliberately kept — this isn't a billing wipe, just the calculation layer

- `ClientBillingProfile` (discounts) — separate concern from *how* a
  base rate is calculated
- `OnforwardingClassification`, `Zone`, `ZoneMapping`,
  `ZoneCountryMapping`, `Territory`, `Route` — the classification/mapping
  layer that rebuilt billing models will plug into
- Every price column already on `shipments` (`base_amount`,
  `surcharge_amount`, `onforwarding_amount`, `discount_amount`,
  `insurance_amount`, `vat_amount`, `total_amount`)
- `ShipmentPricingService` — kept, but simplified: it no longer computes
  `base_amount` itself (that was `RateEngine`'s job). It now reads
  `base_amount` from the context array (defaulting to 0) and still
  correctly handles discount, insurance, onforwarding, and VAT — none of
  which depend on how the base freight charge was calculated. Every
  booking endpoint (client portal, API integrators, staff walk-in) still
  calls it the same way; base pricing is just 0 until a given model is
  rebuilt and starts populating that context value.

### Step one of the rebuild: a reference list, nothing more

`Setting::BILLING_MODELS` — the fixed catalog of known billing-model
*types* (Flat, Distance-Based, Weight-Based, Zone-to-Zone,
Origin-Destination, Volumetric, Hybrid, Service-Type Multiplier,
Time-Based Surcharge, Contract, Truckload, Carton Rate). Same pattern as
`Zone::TIERS`/`Zone::TYPES` — a plain PHP array, not a database table,
since each entry is a piece of calculation logic that doesn't exist yet,
not a row of data.

**No calculation logic exists behind any of these right now.** This is
purely a checklist on **Setups → Company Settings → Supported billing
models** — which of the 12 this business actually uses. Defaults to all
12 checked (via `BrandingServiceProvider`'s fallback) so nothing about
existing config assumes a narrower set until you deliberately narrow it.

Next: pick one model from that list, and we build it — its
configuration screen, its rate table (if it needs one), and its actual
calculation logic — before moving to the next.

### Files

```
database/migrations/2026_01_28_000001_drop_billing_model_tables.php
database/migrations/2026_01_28_000002_add_supported_billing_models_to_settings_table.php
app/Models/Setting.php   (BILLING_MODELS catalog)
app/Models/Shipment.php, Zone.php   (stale references removed)
app/Providers/BrandingServiceProvider.php   (supported_billing_models overlay)
app/Http/Controllers/Web/SettingsController.php   (checklist validation)
app/Http/Controllers/Api/ClientController.php, ClientShipmentController.php, ShipmentController.php   (RateCard lookups removed)
app/Services/ShipmentPricingService.php   (rewritten: base_amount from context, not RateEngine)
resources/views/settings/edit.blade.php   (Supported Billing Models checklist)
resources/views/components/layouts/app.blade.php   (Rate Cards nav item removed)
routes/web.php, routes/api.php   (rate-cards/rates routes removed)

DELETED:
app/Models/RateCard.php, ZoneRateMatrix.php, ZoneWeightRate.php, CartonRate.php
app/Services/RateEngine.php
app/Http/Controllers/Web/RateCardController.php
app/Http/Controllers/Api/RateController.php (dead duplicate from Increment 3)
resources/views/rate-cards/
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 42 — Billing Model Catalog Starts Empty

Corrects Increment 41's reference list: `Setting::BILLING_MODELS` had all
12 known model names pre-listed (unchecked, but still visibly present)
even though none of them had been built yet — misleading, since a name
sitting in that list implies it's usable.

`Setting::BILLING_MODELS` is now genuinely empty. It gains an entry only
as the **last step** of actually building that model — its config
screen, its rate table if it needs one, and its real calculation logic —
never ahead of that work. The Company Settings checklist now shows "No
billing models have been built yet" instead of a blank grid when the
catalog is empty, and will show real checkboxes only for models that
have actually been completed.

Next model discussed and picked gets added here as part of building it —
not before.

### Files

```
app/Models/Setting.php   (BILLING_MODELS emptied)
resources/views/settings/edit.blade.php   (empty-state message)
```

No migration needed.

## Increment 43 — Service Type Becomes a Real Entity

First concrete piece of the Standard Billing rebuild (spec discussed in
chat): Service Type moves from a flat, enforced list of strings to a
proper, creatable table.

### What changed

- **New `service_types` table** (name, code, active) with full CRUD at
  **Setups → Billing → Service Types** — created and managed exactly
  like every other setup entity in this app, not configured as a
  settings blob
- **`settings.service_names` removed entirely** — it was a JSON array of
  free-typed strings ("Express", "Economy") with no real entity behind
  them and no referential integrity. `service_types` is now the single
  source of truth
- **`shipments.service_type` (a free string) converted to
  `shipments.service_type_id` (a real FK)** — every booking endpoint
  (client portal, API integrators, staff walk-in) now validates
  `service_type_id` against the real table instead of accepting any
  string. Every view that displayed the raw string now shows
  `$shipment->serviceType->name` via eager-loading

### Reconciled with the Zone Matrix spec — no changes needed there

Walked through the "Zoning & Standard Billing Specification" against
what's already built:

- The spec's Zone Matrix (origin_state, destination_state, zone_number)
  **is** our existing `ZoneMapping` table — same three columns, `zone_id`
  pointing to a real `Zone` record instead of a bare integer, bidirectional
  by design exactly as already confirmed. Nothing to change.
- `shipping_type` (domestic/international) doesn't need a manual field
  anywhere — it's already implicit in which system resolved the zone
  (`ZoneMapping` for domestic, `ZoneCountryMapping` for international).
  Per discussion, this gets auto-derived and stamped onto the
  **shipment** record for reporting purposes once the tariff calculation
  is built — not a field anyone picks.
- The spec's fixed `zone1_charge`...`zone4_charge` columns won't be used
  as-is — a courier business isn't guaranteed to always have exactly 4
  zones. The upcoming tariff will use a proper child table (one row per
  zone) instead, so the number of zones can be anything without a schema
  change.

### Next

The tariff table itself (`standard_billing_tariffs` +
`tariff_zone_prices`, keyed off `service_type_id` and weight bands) and
the calculation logic that consumes it — building those next.

### Files

```
database/migrations/2026_01_29_000001_create_service_types_table.php
database/migrations/2026_01_29_000002_convert_shipments_service_type_to_fk.php
app/Models/ServiceType.php
app/Models/Setting.php   (service_names removed)
app/Models/Shipment.php   (service_type_id, serviceType() relation)
app/Http/Controllers/Web/ServiceTypeController.php
app/Http/Controllers/Web/SettingsController.php, DashboardController.php, ShipmentController.php
app/Http/Controllers/Api/ClientController.php, ClientShipmentController.php, ShipmentController.php
app/Providers/BrandingServiceProvider.php   (service_names overlay removed)
config/branding.php   (service_names default removed)
resources/views/service-types/   (index + form)
resources/views/settings/edit.blade.php   (Service Names section removed)
resources/views/shipments/, dashboard/index.blade.php   (display via serviceType relation)
resources/views/components/layouts/app.blade.php   (Service Types nav item)
routes/web.php
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 44 — Pricing Engine: Standard Billing + Rate Checker

The first real billing model, built exactly per the "Zoning & Standard
Billing Specification" discussed in chat, plus a model-agnostic quote
checker on top of it.

### The Pricing Engine

`PricingEngine::quote(array $context): array` — the single entry point
every quote and booking now goes through. Looks up the requested
`ServiceType`, dispatches to whichever billing model it's assigned
(`ServiceType::billing_model`), and returns `base_amount`,
`transit_days`, `shipping_type`, `zone_id`. Adding a second billing
model later is one more `match` arm inside it — nothing about its
contract changes for any caller.

**Never returns a guessed price.** Throws `PricingUnavailableException`
whenever the service type has no model assigned, the model isn't
implemented, the route has no zone mapping, or no tariff matches — per
the spec's explicit rule: *"the shipment should not be rated, and the
user should receive an error indicating that the route or tariff has
not been configured."* Every booking endpoint now catches this and
returns a 422 with that message — no shipment is created.

### Standard Billing — the spec, exactly

- `standard_billing_tariffs` (service_type_id, min_weight, max_weight,
  additional_weight) — a weight band per service type
- `tariff_zone_prices` (tariff_id, zone_id, charge, additional_charge,
  transit_days) — **one row per zone**, not fixed `zone1`..`zoneN`
  columns, so the number of zones a business has is never a schema
  concern
- Overage: weight beyond a tariff's `max_weight` is billed in
  `additional_weight`-sized increments at the resolved zone's
  `additional_charge` — the exact formula from the spec, verified
  against both worked examples in the document
- If weight exceeds every configured band, the highest band is used as
  a base with overage still applied — a shipment never fails to price
  just for being heavier than anticipated

**`shipping_type` is never a manual field**, per your direction — it's
auto-derived in `PricingEngine::resolveZoneAndType()`: both sides
resolve to Nigerian cities → domestic (via `ZoneMapping`); either side
is a foreign country → international (via `ZoneCountryMapping`,
destination checked first, then origin for inbound). Stamped onto the
shipment record purely for reporting.

Management screens at **Billing → Standard Billing**: create a tariff,
then add zone prices to it one at a time, same pattern as the deleted
`zone_weight_rates` editor.

### Rate Checker

**Billing → Rate Checker** — a form (service type, domestic/international
toggle, weight) that calls `PricingEngine::quote()` directly and shows
the result or the exact error a real booking would produce, without
creating a shipment. Built model-agnostically on purpose: it doesn't
know anything about Standard Billing specifically, so the moment a
second billing model exists and a service type is assigned to it, the
checker prices that too — no changes needed here.

### Every booking endpoint now actually prices

`ClientController::quote()`, `ClientShipmentController::store()`, and
staff `ShipmentController::store()` all now call `PricingEngine::quote()`
before `ShipmentPricingService::priceShipment()` — `base_amount` is real
now, not a placeholder 0. `promised_delivery_at` is derived from the
returned `transit_days`. All three accept optional
`origin_country_id`/`destination_country_id` for international routing.

### Files

```
database/migrations/2026_01_30_000001_add_billing_model_to_service_types_table.php
database/migrations/2026_01_30_000002_create_standard_billing_tariff_tables.php
database/migrations/2026_01_30_000003_add_shipping_type_to_shipments_table.php
app/Models/StandardBillingTariff.php, TariffZonePrice.php
app/Models/ServiceType.php   (billing_model)
app/Models/Setting.php   (standard_billing added to BILLING_MODELS)
app/Models/Shipment.php   (shipping_type)
app/Services/PricingEngine.php, PricingUnavailableException.php
app/Http/Controllers/Web/StandardBillingController.php, RateCheckerController.php
app/Http/Controllers/Web/ServiceTypeController.php   (billing_model field)
app/Http/Controllers/Api/ClientController.php, ClientShipmentController.php, ShipmentController.php   (PricingEngine wired in)
resources/views/standard-billing/, rate-checker/
resources/views/service-types/   (billing_model field/column)
resources/views/components/layouts/app.blade.php   (nav items)
routes/web.php
```

### To apply locally

```powershell
php artisan migrate
```

### Setup order for a working quote

1. Setups → Billing → Service Types — set a service type's Billing model
   to "Standard Billing"
2. Setups → Billing → Zone Mapping — assign zones to state pairs (or
   countries, for international)
3. Setups → Billing → Standard Billing — create a tariff for that
   service type's weight range, then add a price per zone
4. Setups → Billing → Rate Checker — verify it prices correctly

## Increment 45 — Standard Billing Tariff Lookup Made Deterministic

Self-audit fix on Increment 44: the tariff-matching query had no
explicit `orderBy`, so if two tariffs for the same service type ever end
up with overlapping weight bands (nothing currently validates against
that), which one won would depend on database row order — unpredictable,
and could differ between environments. Added `orderBy('min_weight')` so
the narrowest/lowest-starting band always wins consistently, regardless
of insertion order or database engine.

No schema change, no behavior change for the normal case (non-overlapping
bands) — only makes the edge case predictable instead of undefined.

### Files

```
app/Services/PricingEngine.php
```

## Increment 46 — Rate Checker: State/District Selection, Full Breakdown, Filtered by Billing Model

Three related improvements to the Rate Checker, based on feedback after
Increment 44.

### State and District added — the checker now runs the full pricing pipeline

Previously the checker only called `PricingEngine::quote()` (base
freight only) — there was no way to preview onforwarding at all, since
that depends on `origin_district_id`/`destination_district_id`, which
the form never collected. It now:

- Collects **State** (new — cascades the City list), **City**, and
  **District** (new, optional) for both origin and destination
- Calls `ShipmentPricingService::priceShipment()` with the full context
  after getting `base_amount` from `PricingEngine`, so the result shows
  the complete breakdown — base, surcharges, onforwarding, discount,
  insurance, VAT, total — exactly what a real booking would produce
  (minus any client-specific discount, since no client is selected here)

### `PricingEngine` now accepts a State directly

`resolveZoneAndType()` previously required a City (and derived the state
from it internally) — a state can now be passed directly
(`origin_state_id`/`destination_state_id`), with City still supported as
before. A city is one way to arrive at a state, not the only way; this
lets the Rate Checker (and any future caller) resolve a zone from just a
state when that's all that's known or relevant.

### Billing Model + Route Type now filter Service Type, not the other way around

Previously Service Type was the first, independent field. Now Billing
Model and Route Type come first, and Service Type is filtered live
(client-side, no page reload) to only the ones using the selected
billing model — picking a mismatched combination isn't possible anymore.
Route Type continues to control which fields show (State/City/District
for domestic, Country for international) exactly as before.

### Files

```
app/Services/PricingEngine.php   (resolveZoneAndType accepts state_id directly)
app/Http/Controllers/Web/RateCheckerController.php   (full pipeline, state/district support)
resources/views/rate-checker/index.blade.php   (rebuilt: Billing Model/Route Type first, State→City→District cascading, full breakdown)
```

No migration needed.

## Increment 47 — Route Type Now Actually Restricts Service Type

Closes the gap flagged at the end of Increment 46: Route Type controlled
which *fields* showed on the Rate Checker, but didn't actually filter
which *service types* were selectable — only Billing Model did.

`service_types.route_type` (nullable enum: `domestic`, `international`)
— a deliberate restriction on the service type's own definition (e.g.
an "International Express" service that shouldn't appear for domestic
bookings). Nullable means "offered for both," the default, so every
existing service type keeps working exactly as before until someone
deliberately restricts one.

This is a different kind of field from `shipments.shipping_type`
(Increment 44) — that one is never manually picked, auto-derived per
shipment from the resolved zone. This one is a one-time configuration
choice on the service type itself, made by whoever manages Service
Types, same as any other setup field.

Rate Checker's filter now checks both conditions together — a service
type only shows when it matches the selected Billing Model **and** the
selected Route Type. Switching either one re-filters live.

### Files

```
database/migrations/2026_01_31_000001_add_route_type_to_service_types_table.php
app/Models/ServiceType.php   (route_type)
app/Http/Controllers/Web/ServiceTypeController.php   (route_type validation)
resources/views/service-types/form.blade.php, index.blade.php   (field + column)
resources/views/rate-checker/index.blade.php   (filterServiceTypes() checks both billing model and route type)
```

### To apply locally

```powershell
php artisan migrate
```

## Increment 48 — Standard Billing Zone Prices: One Screen, Not One Zone at a Time

Every field (`additional_weight`, `charge`, `additional_charge`,
`transit_days`) already existed and worked correctly since Increment 44
— what was actually incomplete was the workflow: setting up a tariff's
zone prices meant submitting a separate form for every single zone, one
at a time. With 4-6 zones, that's 4-6 round trips to fully configure one
tariff.

### Now: every zone listed, filled in inline, saved together

`StandardBillingController::edit()` now merges **every** zone (not just
already-priced ones) with its existing price if one exists. The form
shows one row per zone with editable Charge/Additional Charge/Transit
Days fields directly in the table — pre-filled where a price already
exists, blank otherwise — and a single **Save zone prices** button
commits all of them in one request.

Leaving a zone's Charge blank means "this tariff doesn't cover that
zone" — saving removes any existing price for it. Filling it in
upserts. `updateZonePrices()` (replacing the old `addZonePrice()`/
`destroyZonePrice()` pair) reports back exactly how many were saved and
how many were cleared.

The tariff's own fields (weight band, `additional_weight`) are
unchanged — Increment 44 already had those right; this increment is
purely about the zone-pricing sub-table underneath them.

### Files

```
app/Http/Controllers/Web/StandardBillingController.php   (edit() merges every zone; updateZonePrices() replaces the one-at-a-time pair)
resources/views/standard-billing/form.blade.php   (single inline-editable table, one save)
routes/web.php   (one PUT route replaces the old POST+DELETE pair)
```

No migration needed — this increment is controller and view logic only.

## Increment 49 — Zone Prices: Simple Form Restored + CSV Export/Import

Reverses Increment 48's bulk inline-table approach — it was a worse UX
than the plain form it replaced. Restored the clean, simple pattern
(list of existing prices + one plain form below to add/update a single
zone), matching how every other setup screen in this app already works,
and added what actually solves the "many zones to enter" problem
properly: CSV export/import, using the same `CsvService`
pattern already proven on Zone Mapping, Countries, States, etc.

### What changed

- `addZonePrice()`/`destroyZonePrice()` restored — one zone at a time,
  via a plain form (Zone / Charge / Additional charge / Transit days /
  Save)
- New `exportZonePrices()`/`importZonePrices()` — scoped to a single
  tariff (each tariff is its own weight band/service type, so its zone
  prices are its own CSV, not a global one). Natural key is the zone's
  **code**, same convention used throughout every other CSV round trip
  in this app — download, fill in the rest of the zones in a
  spreadsheet, re-upload
- `<x-csv-actions>` (the shared component from Increment 39) now appears
  on the tariff edit page, right above the zone-prices table

### Files

```
app/Http/Controllers/Web/StandardBillingController.php   (addZonePrice/destroyZonePrice restored, exportZonePrices/importZonePrices added)
resources/views/standard-billing/form.blade.php   (simple list + form restored, CSV bar added)
routes/web.php
```

No migration needed.
