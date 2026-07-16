<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zone extends Model
{
    protected $fillable = ['name', 'code', 'type', 'tier', 'coverage_description', 'hub_id', 'geofence'];

    protected $casts = ['geofence' => 'array'];

    /**
     * The A–F courier-industry tier model — only meaningful for
     * DOMESTIC zones (a "Zone C" doesn't describe an international
     * grouping the way "West Africa" does). `type` (domestic/
     * international) is the required, primary classification when
     * creating a zone; `tier` is an optional refinement only offered in
     * the UI when type = domestic. Reference data only — the actual
     * price between zones still lives entirely in ZoneRateMatrix
     * (Billing → Zone Mapping).
     */
    public const TIERS = [
        'A' => ['label' => 'Zone A', 'coverage' => 'Same city / Local delivery', 'purpose' => 'Lowest tariff'],
        'B' => ['label' => 'Zone B', 'coverage' => 'Nearby towns within the same state', 'purpose' => 'Short-distance tariff'],
        'C' => ['label' => 'Zone C', 'coverage' => 'Neighboring states', 'purpose' => 'Medium-distance tariff'],
        'D' => ['label' => 'Zone D', 'coverage' => 'Regional destinations', 'purpose' => 'Higher tariff'],
        'E' => ['label' => 'Zone E', 'coverage' => 'Long-distance/interstate', 'purpose' => 'Premium tariff'],
        'F' => ['label' => 'Zone F', 'coverage' => 'Remote or hard-to-reach areas', 'purpose' => 'Highest tariff, possible surcharge'],
    ];

    public const TYPES = [
        'domestic' => 'Domestic',
        'international' => 'International',
    ];

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    public function tierLabel(): ?string
    {
        return self::TIERS[$this->tier]['label'] ?? null;
    }

    public function tierPurpose(): ?string
    {
        return self::TIERS[$this->tier]['purpose'] ?? null;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
