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
