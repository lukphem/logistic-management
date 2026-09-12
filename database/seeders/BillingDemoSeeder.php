<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\FleetBillingTariff;
use App\Models\Hub;
use App\Models\OriginDestinationTariff;
use App\Models\Outlet;
use App\Models\Region;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Models\StandardBillingTariff;
use App\Models\State;
use App\Models\TariffZonePrice;
use App\Models\Territory;
use App\Models\VehicleType;
use App\Models\Zone;
use App\Models\ZoneMapping;
use Illuminate\Database\Seeder;

/**
 * Everything needed for the system to actually PRICE a shipment on a
 * freshly seeded database — territories, hubs/outlets, zones and
 * zone mappings, vehicle types, one product per billing model, and
 * tariffs for all three billing models. Every value here is a
 * reasonable placeholder (explicitly requested over real pricing,
 * which wasn't available yet) — meant to be adjusted through the
 * normal Billing screens, not treated as real rates.
 *
 * Idempotent throughout (firstOrCreate/updateOrCreate) — safe to run
 * on every migrate:fresh --seed without creating duplicates if run
 * more than once against the same data.
 */
class BillingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->assignTerritoriesAndAirports();
        $zones = Zone::ensureDefaultZones();
        $this->generateZoneMappings();
        $hubs = $this->seedHubsAndOutlets();
        $vehicleTypes = $this->seedVehicleTypes();
        $serviceTypes = $this->seedServiceTypes();
        $this->seedStandardBillingTariffs($serviceTypes['express'], $zones);
        $this->seedStandardBillingTariffs($serviceTypes['standard'], $zones, cheaper: true);
        $this->seedOriginDestinationTariffs($serviceTypes['interstate_freight']);
        $this->seedFleetBillingTariffs($serviceTypes['fleet_delivery'], $vehicleTypes);

        Setting::current()->update([
            'supported_billing_models' => array_keys(Setting::BILLING_MODELS),
        ]);
    }

    /**
     * Nigeria's 6 standard geopolitical zones, used here as
     * Territories (matches how ZoneMapping::determineDefaultZoneTier
     * already reasons about "same territory" vs "different
     * territory"). has_airport is a reasonable placeholder set of
     * states with a major airport — not exhaustive, just enough to
     * produce a realistic mix of zone tiers 3 and 4.
     */
    private function assignTerritoriesAndAirports(): void
    {
        $territoryStates = [
            'North Central' => ['Benue', 'Kogi', 'Kwara', 'Nasarawa', 'Niger', 'Plateau', 'Federal Capital Territory'],
            'North East' => ['Adamawa', 'Bauchi', 'Borno', 'Gombe', 'Taraba', 'Yobe'],
            'North West' => ['Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Sokoto', 'Zamfara'],
            'South East' => ['Abia', 'Anambra', 'Ebonyi', 'Enugu', 'Imo'],
            'South South' => ['Akwa Ibom', 'Bayelsa', 'Cross River', 'Delta', 'Edo', 'Rivers'],
            'South West' => ['Ekiti', 'Lagos', 'Ogun', 'Ondo', 'Osun', 'Oyo'],
        ];

        // Explicit, guaranteed-unique codes — the naive "first 4 letters
        // with spaces stripped" approach collides badly here: North
        // Central/East/West all reduce to "NORT", and South East/South/
        // West all reduce to "SOUT".
        $territoryCodes = [
            'North Central' => 'NC',
            'North East' => 'NE',
            'North West' => 'NW',
            'South East' => 'SE',
            'South South' => 'SS',
            'South West' => 'SW',
        ];

        $airportStates = [
            'Lagos', 'Federal Capital Territory', 'Kano', 'Rivers', 'Enugu', 'Kaduna',
            'Oyo', 'Edo', 'Cross River', 'Imo', 'Sokoto', 'Borno', 'Adamawa', 'Plateau',
        ];

        $nigeria = Country::where('code', 'NG')->first();
        if (! $nigeria) {
            return;
        }

        foreach ($territoryStates as $territoryName => $stateNames) {
            $territory = Territory::firstOrCreate(
                ['name' => $territoryName],
                ['code' => $territoryCodes[$territoryName]]
            );

            State::where('country_id', $nigeria->id)
                ->whereIn('name', $stateNames)
                ->update(['territory_id' => $territory->id]);
        }

        State::where('country_id', $nigeria->id)->whereIn('name', $airportStates)->update(['has_airport' => true]);
        State::where('country_id', $nigeria->id)->whereNotIn('name', $airportStates)->update(['has_airport' => false]);
    }

    /**
     * Same idempotent pair-generation ZoneMappingController::generateDomestic()
     * uses (firstOrCreate per unordered state pair, tier from
     * ZoneMapping::determineDefaultZoneTier) — replicated here rather
     * than calling the controller action directly from a seeder.
     * Anything generated here can still be individually reassigned
     * afterward via the normal Zone Mapping screen, same as if it had
     * been generated through that screen's own button.
     */
    private function generateZoneMappings(): void
    {
        $nigeria = Country::where('code', 'NG')->first();
        if (! $nigeria) {
            return;
        }

        $defaultZones = Zone::ensureDefaultZones();
        $states = State::where('country_id', $nigeria->id)->get()->values();

        foreach ($states as $state) {
            ZoneMapping::firstOrCreate(
                ['state_a_id' => $state->id, 'state_b_id' => $state->id],
                ['zone_id' => $defaultZones[ZoneMapping::determineDefaultZoneTier($state, $state)]->id]
            );
        }

        for ($i = 0; $i < $states->count(); $i++) {
            for ($j = $i + 1; $j < $states->count(); $j++) {
                $stateA = $states[$i];
                $stateB = $states[$j];
                [$a, $b] = $stateA->id < $stateB->id ? [$stateA->id, $stateB->id] : [$stateB->id, $stateA->id];

                ZoneMapping::firstOrCreate(
                    ['state_a_id' => $a, 'state_b_id' => $b],
                    ['zone_id' => $defaultZones[ZoneMapping::determineDefaultZoneTier($stateA, $stateB)]->id]
                );
            }
        }
    }

    /**
     * Placeholder hubs/outlets in 3 major cities, since real
     * locations weren't available yet — each city's operational_hub_id
     * is set so the Client form's Outlet dropdown (Client -> Account
     * restructure) actually has something to resolve once a client's
     * City is picked.
     *
     * @return array<string, Hub>
     */
    private function seedHubsAndOutlets(): array
    {
        $region = Region::firstOrCreate(['code' => 'NG-DEMO'], ['name' => 'Nigeria Operations']);

        $hubCities = [
            'lagos' => ['state' => 'Lagos', 'city' => 'Ikeja', 'hub_name' => 'Lagos Hub', 'hub_code' => 'LOS-HUB'],
            'abuja' => ['state' => 'Federal Capital Territory', 'city' => 'Abuja Municipal', 'hub_name' => 'Abuja Hub', 'hub_code' => 'ABV-HUB'],
            'port_harcourt' => ['state' => 'Rivers', 'city' => 'Port Harcourt', 'hub_name' => 'Port Harcourt Hub', 'hub_code' => 'PHC-HUB'],
        ];

        $hubs = [];

        foreach ($hubCities as $key => $data) {
            $city = City::whereHas('state', fn ($q) => $q->where('name', $data['state']))
                ->where('name', $data['city'])
                ->first();

            $hub = Hub::firstOrCreate(
                ['code' => $data['hub_code']],
                ['region_id' => $region->id, 'city_id' => $city?->id, 'name' => $data['hub_name'], 'is_active' => true]
            );

            if ($city && ! $city->operational_hub_id) {
                $city->update(['operational_hub_id' => $hub->id]);
            }

            Outlet::firstOrCreate(
                ['code' => $data['hub_code'] . '-1'],
                ['hub_id' => $hub->id, 'name' => $data['hub_name'] . ' — Main Outlet', 'is_active' => true]
            );

            $hubs[$key] = $hub;
        }

        return $hubs;
    }

    /**
     * @return array<string, VehicleType>
     */
    private function seedVehicleTypes(): array
    {
        $definitions = [
            'bike' => ['name' => 'Dispatch Bike', 'code' => 'BIKE', 'max_weight_capacity' => 15, 'is_open_body' => false],
            'van' => ['name' => 'Delivery Van', 'code' => 'VAN', 'max_weight_capacity' => 500, 'is_open_body' => false],
            'truck' => ['name' => 'Box Truck', 'code' => 'TRUCK', 'max_weight_capacity' => 3000, 'is_open_body' => false],
        ];

        $types = [];

        foreach ($definitions as $key => $data) {
            $types[$key] = VehicleType::firstOrCreate(['code' => $data['code']], [...$data, 'is_active' => true]);
        }

        return $types;
    }

    /**
     * One product per billing model at minimum, so every model
     * actually has something bookable — Express/Standard share
     * Standard Billing (different price tiers, same zone/weight
     * model), Interstate Freight uses Origin-to-Destination, Fleet
     * Delivery uses Fleet Billing.
     *
     * @return array<string, ServiceType>
     */
    private function seedServiceTypes(): array
    {
        $definitions = [
            'express' => ['name' => 'Express', 'code' => 'EXP', 'billing_model' => 'standard_billing', 'route_type' => 'domestic'],
            'standard' => ['name' => 'Standard', 'code' => 'STD', 'billing_model' => 'standard_billing', 'route_type' => 'domestic'],
            'interstate_freight' => ['name' => 'Interstate Freight', 'code' => 'ISF', 'billing_model' => 'origin_destination_billing', 'route_type' => 'domestic'],
            'fleet_delivery' => ['name' => 'Fleet Delivery', 'code' => 'FLT', 'billing_model' => 'fleet_billing', 'route_type' => 'domestic'],
        ];

        $types = [];

        foreach ($definitions as $key => $data) {
            $types[$key] = ServiceType::firstOrCreate(['code' => $data['code']], [...$data, 'is_active' => true]);
        }

        return $types;
    }

    /**
     * Two weight bands (0–2kg, 2–10kg with 1kg overage increments)
     * priced across all 4 zone tiers — placeholder Naira figures that
     * scale up with zone distance, not real rates.
     */
    private function seedStandardBillingTariffs(ServiceType $serviceType, array $zones, bool $cheaper = false): void
    {
        $multiplier = $cheaper ? 0.7 : 1.0;

        $bands = [
            ['min' => 0, 'max' => 2, 'limit' => 2, 'additional' => 1, 'base' => [1 => 1500, 2 => 2200, 3 => 3200, 4 => 4500], 'per_kg' => [1 => 200, 2 => 300, 3 => 450, 4 => 600]],
            ['min' => 2, 'max' => 10, 'limit' => 10, 'additional' => 1, 'base' => [1 => 2800, 2 => 4000, 3 => 5800, 4 => 7800], 'per_kg' => [1 => 250, 2 => 350, 3 => 500, 4 => 650]],
        ];

        foreach ($bands as $band) {
            $tariff = StandardBillingTariff::firstOrCreate(
                ['service_type_id' => $serviceType->id, 'min_weight' => $band['min'], 'max_weight_limit' => $band['limit']],
                ['max_weight' => $band['max'], 'additional_weight' => $band['additional'], 'is_active' => true]
            );

            foreach ($zones as $tier => $zone) {
                TariffZonePrice::firstOrCreate(
                    ['tariff_id' => $tariff->id, 'zone_id' => $zone->id],
                    [
                        'charge' => round($band['base'][$tier] * $multiplier),
                        'additional_charge' => round($band['per_kg'][$tier] * $multiplier),
                        'transit_days' => $tier,
                    ]
                );
            }
        }
    }

    /**
     * A handful of sample Lagos-anchored lanes, since real routes
     * weren't available yet — enough to demonstrate the billing model
     * works, not a real route network.
     */
    private function seedOriginDestinationTariffs(ServiceType $serviceType): void
    {
        $lanes = [
            ['origin' => 'Lagos', 'destination' => 'Federal Capital Territory', 'base' => 5500, 'per_kg' => 400, 'transit' => 2],
            ['origin' => 'Lagos', 'destination' => 'Rivers', 'base' => 5000, 'per_kg' => 380, 'transit' => 2],
            ['origin' => 'Lagos', 'destination' => 'Kano', 'base' => 6500, 'per_kg' => 450, 'transit' => 3],
            ['origin' => 'Lagos', 'destination' => 'Oyo', 'base' => 3200, 'per_kg' => 250, 'transit' => 1],
        ];

        $nigeria = Country::where('code', 'NG')->first();
        if (! $nigeria) {
            return;
        }

        foreach ($lanes as $lane) {
            $originState = State::where('country_id', $nigeria->id)->where('name', $lane['origin'])->first();
            $destinationState = State::where('country_id', $nigeria->id)->where('name', $lane['destination'])->first();

            if (! $originState || ! $destinationState) {
                continue;
            }

            OriginDestinationTariff::firstOrCreate(
                [
                    'service_type_id' => $serviceType->id,
                    'origin_state_id' => $originState->id,
                    'destination_state_id' => $destinationState->id,
                    'min_weight' => 0,
                ],
                [
                    'max_weight' => 10,
                    'max_weight_limit' => 10,
                    'base_charge' => $lane['base'],
                    'additional_weight' => 1,
                    'additional_charge' => $lane['per_kg'],
                    'transit_days' => $lane['transit'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * One flat rate per vehicle type, since a real per-lane fleet
     * rate card wasn't available yet.
     */
    private function seedFleetBillingTariffs(ServiceType $serviceType, array $vehicleTypes): void
    {
        $rates = [
            'bike' => ['base' => 2000, 'per_kg' => 150],
            'van' => ['base' => 15000, 'per_kg' => 80],
            'truck' => ['base' => 60000, 'per_kg' => 40],
        ];

        foreach ($vehicleTypes as $key => $vehicleType) {
            FleetBillingTariff::firstOrCreate(
                ['service_type_id' => $serviceType->id, 'vehicle_type_id' => $vehicleType->id, 'min_weight' => 0],
                [
                    'max_weight' => $vehicleType->max_weight_capacity,
                    'max_weight_limit' => $vehicleType->max_weight_capacity,
                    'base_charge' => $rates[$key]['base'],
                    'additional_weight' => 1,
                    'additional_charge' => $rates[$key]['per_kg'],
                    'is_active' => true,
                ]
            );
        }
    }
}
