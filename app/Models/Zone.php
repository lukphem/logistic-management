<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zone extends Model
{
    protected $fillable = ['name', 'code', 'applies_domestic', 'applies_international', 'tier', 'coverage_description', 'hub_id', 'geofence'];

    protected $casts = [
        'geofence' => 'array',
        'applies_domestic' => 'boolean',
        'applies_international' => 'boolean',
    ];

    /**
     * The A–F courier-industry tier model — only meaningful for zones
     * that apply domestically (a "Zone C" doesn't describe an
     * international grouping the way "West Africa" does). Offered in
     * the UI whenever applies_domestic is checked, regardless of
     * whether applies_international is ALSO checked — a zone can be
     * both, and still have a domestic tier for its domestic side.
     * Reference data only — Zone itself never holds a price; whichever
     * billing model actually prices a zone owns that.
     */
    public const TIERS = [
        'A' => ['label' => 'Zone A', 'coverage' => 'Same city / Local delivery', 'purpose' => 'Lowest tariff'],
        'B' => ['label' => 'Zone B', 'coverage' => 'Nearby towns within the same state', 'purpose' => 'Short-distance tariff'],
        'C' => ['label' => 'Zone C', 'coverage' => 'Neighboring states', 'purpose' => 'Medium-distance tariff'],
        'D' => ['label' => 'Zone D', 'coverage' => 'Regional destinations', 'purpose' => 'Higher tariff'],
        'E' => ['label' => 'Zone E', 'coverage' => 'Long-distance/interstate', 'purpose' => 'Premium tariff'],
        'F' => ['label' => 'Zone F', 'coverage' => 'Remote or hard-to-reach areas', 'purpose' => 'Highest tariff, possible surcharge'],
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

    /**
     * "Domestic", "International", or "Domestic + International" — for
     * display wherever the old typeLabel() used to be shown.
     */
    public function applicabilityLabel(): string
    {
        return match (true) {
            $this->applies_domestic && $this->applies_international => 'Domestic + International',
            $this->applies_domestic => 'Domestic',
            $this->applies_international => 'International',
            default => 'Not set',
        };
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
                    'applies_domestic' => true,
                    'coverage_description' => $definition['coverage'],
                ]
            );
        }

        return $zones;
    }
}
