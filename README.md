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
