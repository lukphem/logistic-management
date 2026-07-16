<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zone extends Model
{
    protected $fillable = ['name', 'code', 'tier', 'coverage_description', 'hub_id', 'geofence'];

    protected $casts = ['geofence' => 'array'];

    /**
     * The standard courier-industry zone-tier model. Each entry is
     * [label, default coverage description, billing purpose] — used to
     * populate the tier picker and to suggest a coverage description
     * when one hasn't been typed. Purely reference data for the UI; the
     * actual tariff itself still lives in ZoneRateMatrix (Zone Mapping),
     * not here — a tier describes WHAT KIND of zone this is, not its
     * price.
     */
    public const TIERS = [
        'A' => ['label' => 'Zone A', 'coverage' => 'Same city / Local delivery', 'purpose' => 'Lowest tariff'],
        'B' => ['label' => 'Zone B', 'coverage' => 'Nearby towns within the same state', 'purpose' => 'Short-distance tariff'],
        'C' => ['label' => 'Zone C', 'coverage' => 'Neighboring states', 'purpose' => 'Medium-distance tariff'],
        'D' => ['label' => 'Zone D', 'coverage' => 'Regional destinations', 'purpose' => 'Higher tariff'],
        'E' => ['label' => 'Zone E', 'coverage' => 'Long-distance/interstate', 'purpose' => 'Premium tariff'],
        'F' => ['label' => 'Zone F', 'coverage' => 'Remote or hard-to-reach areas', 'purpose' => 'Highest tariff, possible surcharge'],
        'international' => ['label' => 'International', 'coverage' => 'Countries grouped by region (e.g. West Africa, Europe, North America, Asia)', 'purpose' => 'International tariffs'],
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
}
