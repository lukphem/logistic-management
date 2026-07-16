<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class BrandingServiceProvider extends ServiceProvider
{
    /**
     * Every existing config('branding.*') call site (layout views,
     * ShipmentPricingService's VAT lookup, etc.) keeps working unchanged —
     * this just overwrites the in-memory config array with whatever the
     * setup wizard has saved, before anything else reads it.
     *
     * Guards on Schema::hasTable so this doesn't break `php artisan migrate`
     * itself on a completely fresh install, before the settings table exists.
     */
    public function boot(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $settings = \App\Models\Setting::query()->first();

        if (! $settings) {
            return;
        }

        config([
            'branding.company_name' => $settings->company_name ?? config('branding.company_name'),
            'branding.service_names' => $settings->service_names ?? config('branding.service_names'),
            'branding.colors.primary' => $settings->color_primary ?? config('branding.colors.primary'),
            'branding.colors.secondary' => $settings->color_secondary ?? config('branding.colors.secondary'),
            'branding.vat_percentage' => $settings->vat_percentage ?? config('branding.vat_percentage'),
            'branding.currency' => $settings->currency ?? config('branding.currency'),
            'branding.waybill.thermal_size' => $settings->waybill_thermal_size ?? config('branding.waybill.thermal_size'),
            'branding.waybill.show_qr' => $settings->waybill_show_qr ?? config('branding.waybill.show_qr'),
        ]);
    }
}
