<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalServiceOption extends Model
{
    protected $fillable = ['additional_service_id', 'name', 'charge_type', 'price', 'is_active'];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public const CHARGE_TYPES = [
        'flat' => 'Flat amount',
        'percentage' => 'Percentage of freight',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(AdditionalService::class, 'additional_service_id');
    }

    /**
     * The actual Naira amount this option adds — $price directly for a
     * flat charge, or $price% of the given base freight amount for a
     * percentage charge. $baseAmount is the shipment's base_amount, the
     * same figure VAT is calculated from — not the running total, since
     * an additional service isn't meant to compound on top of other
     * additional services or discounts.
     */
    public function resolveAmount(float $baseAmount): float
    {
        return $this->charge_type === 'percentage'
            ? round($baseAmount * ($this->price / 100), 2)
            : round($this->price, 2);
    }

    /**
     * "₦2,500.00" for a flat option, "2.5% of freight" for a percentage
     * one — for display wherever the option's price needs to be shown
     * without a specific shipment to resolve it against yet (the
     * options list, the Rate Checker's picker before a quote exists).
     */
    public function displayPrice(): string
    {
        return $this->charge_type === 'percentage'
            ? rtrim(rtrim(number_format($this->price, 2), '0'), '.') . '% of freight'
            : number_format($this->price, 2);
    }
}
