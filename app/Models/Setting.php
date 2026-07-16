<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name', 'logo_path', 'service_names',
        'color_primary', 'color_secondary',
        'vat_percentage', 'currency',
        'waybill_thermal_size', 'waybill_show_qr',
        'operating_regions', 'invoice_header', 'invoice_footer',
    ];

    protected $casts = [
        'service_names' => 'array',
        'operating_regions' => 'array',
        'waybill_show_qr' => 'boolean',
        'vat_percentage' => 'float',
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
            'service_names' => config('branding.service_names'),
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
        return $this->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path) : null;
    }
}
