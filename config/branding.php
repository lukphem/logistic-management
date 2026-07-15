<?php

/**
 * Per-deployment branding/setup values.
 * In production these should be read from a `settings` DB table
 * (editable via the admin setup wizard) and cached — this file only
 * defines the defaults/fallbacks and the shape of the config.
 */
return [
    'company_name' => env('COMPANY_NAME', 'Courier Co.'),
    'service_names' => [
        'express' => 'Express',
        'same_day' => 'Same-Day',
        'economy' => 'Economy',
    ],
    'colors' => [
        'primary' => env('BRAND_COLOR_PRIMARY', '#1F3864'),
        'secondary' => env('BRAND_COLOR_SECONDARY', '#F2A900'),
    ],
    'vat_percentage' => env('VAT_PERCENTAGE', 7.5),
    'currency' => env('CURRENCY_CODE', 'NGN'),
    'waybill' => [
        'thermal_size' => '4x6', // or '2x1'
        'show_qr' => true,
    ],
];
