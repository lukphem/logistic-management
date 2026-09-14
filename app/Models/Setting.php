<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name', 'logo_path',
        'color_primary', 'color_secondary', 'login_design',
        'vat_percentage', 'maximum_delivery_attempts', 'volumetric_divisor', 'quote_validity_days', 'currency',
        'tracking_number_format', 'next_tracking_sequence',
        'account_number_format', 'allow_manual_account_number',
        'waybill_thermal_size', 'waybill_show_qr', 'label_design',
        'operating_regions', 'invoice_header', 'invoice_footer',
        'supported_billing_models',
    ];

    protected $casts = [
        'operating_regions' => 'array',
        'waybill_show_qr' => 'boolean',
        'vat_percentage' => 'float',
        'maximum_delivery_attempts' => 'integer',
        'volumetric_divisor' => 'integer',
        'quote_validity_days' => 'integer',
        'next_tracking_sequence' => 'integer',
        'allow_manual_account_number' => 'boolean',
        'supported_billing_models' => 'array',
    ];

    /**
     * The catalog of billing-model TYPES the system actually supports —
     * starts EMPTY on purpose. Nothing goes in here until it's been
     * built: its configuration screen, its rate table (if it needs one),
     * and its real calculation logic, one model at a time. Add an entry
     * here only as the last step of actually building that model — never
     * ahead of it, since a name sitting in this list implies it's usable
     * when it isn't yet.
     */
    public const BILLING_MODELS = [
        'standard_billing' => 'Zoning and Weight',
        'origin_destination_billing' => 'Origin to Destination',
        'fleet_billing' => 'Fleet Billing',
    ];

    /**
     * The company-level master list BILLING_MODELS entries a company
     * has actually chosen to use (Company Settings -> Billing models),
     * filtered down from BILLING_MODELS — every place that lets
     * someone pick a billing model (a new Product/ServiceType, Rate
     * Checker, Create Shipment) should offer this, not the raw
     * BILLING_MODELS constant, so a company that's disabled a model
     * can't have it selected anywhere in the system. Null/empty
     * supported_billing_models means "everything supported" (the only
     * behavior possible before this setting existed), so an install
     * that's never touched this setting sees no change.
     */
    public function supportedBillingModels(): array
    {
        if (empty($this->supported_billing_models)) {
            return self::BILLING_MODELS;
        }

        return array_intersect_key(self::BILLING_MODELS, array_flip($this->supported_billing_models));
    }

    /**
     * Each key has a matching resources/views/auth/designs/{key}.blade.php
     * partial for the login page's left illustration panel.
     */
    public const LOGIN_DESIGNS = [
        'route' => ['label' => 'Route', 'description' => 'A truck driving a dashed route between two map pins.'],
        'warehouse' => ['label' => 'Warehouse', 'description' => 'A warm, warehouse-toned grid of package icons.'],
        'map' => ['label' => 'Map', 'description' => 'A dotted world map with scattered delivery pins.'],
        'gradient' => ['label' => 'Vibrant', 'description' => 'A colorful multi-tone gradient with floating icons.'],
    ];

    /**
     * There is always exactly one settings row (id 1) for this deployment.
     * Falls back to config/branding.php defaults on a fresh install where
     * the setup wizard hasn't been run yet.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'company_name' => config('branding.company_name'),
            'color_primary' => config('branding.colors.primary'),
            'color_secondary' => config('branding.colors.secondary'),
            'vat_percentage' => config('branding.vat_percentage'),
            'currency' => config('branding.currency'),
            'waybill_thermal_size' => config('branding.waybill.thermal_size'),
            'waybill_show_qr' => config('branding.waybill.show_qr'),
        ]);
    }

    public function getLogoUrlAttribute(): ?string
    {
        // Root-relative on purpose, not Storage::disk('public')->url(). That
        // helper builds an ABSOLUTE URL from APP_URL, which typically has no
        // port (e.g. http://localhost) while `php artisan serve` runs on
        // :8000 — the browser then requests the wrong port and the image
        // silently 404s. A root-relative path resolves against whatever
        // host/port the page is actually being viewed on, always.
        return $this->logo_path ? '/storage/' . ltrim($this->logo_path, '/') : null;
    }

    /**
     * Tokens available in Tracking Number Format, shown in Company
     * Settings as a reference — kept next to BILLING_MODELS/
     * LOGIN_DESIGNS as the canonical list so the settings view never
     * has to duplicate it.
     */
    public const TRACKING_NUMBER_TOKENS = [
        '{service_code}' => "The shipment's service type code",
        '{origin_hub}' => 'Origin hub code (blank if unresolved)',
        '{destination_hub}' => 'Destination hub code (blank if unresolved)',
        '{date:FORMAT}' => 'Today\'s date — FORMAT is PHP date() syntax, e.g. {date:ymd} or {date:Y-m-d}',
        '{seq:N}' => 'A running counter, zero-padded to N digits, e.g. {seq:5} -> 00042 — never reset, never repeats',
        '{random:N}' => 'N random uppercase letters/digits',
    ];

    /**
     * Same token engine as tracking numbers, extended with the
     * location tokens client account numbers need. {seq:N} here is
     * NOT the same never-resets counter as tracking numbers — it
     * resets separately for every distinct State+Outlet+Staff
     * combination (see AccountNumberSequence), since that's what
     * "location of business" numbering is meant to convey: the 1st,
     * 2nd, 3rd... client that specific staff member set up at that
     * specific outlet, not a number climbing across the whole company.
     */
    public const ACCOUNT_NUMBER_TOKENS = [
        '{state}' => "The account's State short code (e.g. LA) — 'XX' if no State set",
        '{outlet}' => "The account's Outlet short code (e.g. LOS) — 'XXX' if no Outlet set",
        '{staff}' => 'Short code of the staff member who created the account',
        '{seq:N}' => 'A counter zero-padded to N digits, resetting separately for each State+Outlet+Staff combination',
        '{date:FORMAT}' => 'Today\'s date — FORMAT is PHP date() syntax',
        '{random:N}' => 'N random uppercase letters/digits',
    ];

    /**
     * Atomically claims and returns the next sequence number for
     * {seq:N} — locks the settings row for the duration of the
     * transaction so two shipments booked at the same instant still get
     * different numbers, then increments for the next caller.
     */
    public function claimNextTrackingSequence(): int
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $locked = static::where('id', $this->id)->lockForUpdate()->first();
            $current = $locked->next_tracking_sequence;
            $locked->update(['next_tracking_sequence' => $current + 1]);

            return $current;
        });
    }
}
