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
     * the UI when type = domestic. Reference data only — Zone itself
     * never holds a price; whichever billing model actually prices a
     * zone (being rebuilt one model at a time) owns that.
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

    /**
     * Every state-pair route assigned to this zone — see ZoneMapping.
     */
    public function zoneMappings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ZoneMapping::class);
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

    /**
     * The 4 standard domestic zones the tier rule
     * (ZoneMapping::determineDefaultZoneTier) assigns generated state
     * pairs into — created once, reused every time
     * ZoneMappingController::generateDomestic() runs. Returns them keyed
     * by tier (1–4) for easy lookup.
     *
     * @return array<int, Zone>
     */
    public static function ensureDefaultZones(): array
    {
        $definitions = [
            1 => ['name' => 'Zone 1', 'code' => 'Z1', 'coverage' => 'Same state'],
            2 => ['name' => 'Zone 2', 'code' => 'Z2', 'coverage' => 'Same territory, different state'],
            3 => ['name' => 'Zone 3', 'code' => 'Z3', 'coverage' => 'Territory to territory, both states have an airport'],
            4 => ['name' => 'Zone 4', 'code' => 'Z4', 'coverage' => 'Territory to territory, at least one state without an airport'],
        ];

        $zones = [];

        foreach ($definitions as $tier => $definition) {
            $zones[$tier] = self::firstOrCreate(
                ['name' => $definition['name']],
                [
                    'code' => $definition['code'],
                    'type' => 'domestic',
                    'coverage_description' => $definition['coverage'],
                ]
            );
        }

        return $zones;
    }
}
