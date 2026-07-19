<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name', 'logo_path',
        'color_primary', 'color_secondary', 'login_design',
        'vat_percentage', 'volumetric_divisor', 'currency',
        'waybill_thermal_size', 'waybill_show_qr',
        'operating_regions', 'invoice_header', 'invoice_footer',
        'supported_billing_models',
    ];

    protected $casts = [
        'operating_regions' => 'array',
        'waybill_show_qr' => 'boolean',
        'vat_percentage' => 'float',
        'volumetric_divisor' => 'integer',
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
        'standard_billing' => 'Standard Billing (Zone + Weight)',
    ];

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
}
