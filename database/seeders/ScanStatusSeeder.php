<?php

namespace Database\Seeders;

use App\Models\ScanStatus;
use Illuminate\Database\Seeder;

class ScanStatusSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'booked', 'label' => 'Booked', 'is_terminal' => false, 'notify_customer' => true, 'is_first_touch' => false],
            ['key' => 'picked_up', 'label' => 'Picked Up', 'is_terminal' => false, 'notify_customer' => false, 'is_first_touch' => true],
            ['key' => 'dropped_off', 'label' => 'Dropped Off', 'is_terminal' => false, 'notify_customer' => false, 'is_first_touch' => true],
            ['key' => 'in_transit', 'label' => 'Transfer to Another Unit for Processing', 'is_terminal' => false, 'notify_customer' => false, 'is_first_touch' => false],
            ['key' => 'transloaded', 'label' => 'Transloaded', 'is_terminal' => false, 'notify_customer' => false, 'is_first_touch' => false],
            ['key' => 'arrived_at_hub', 'label' => 'Arrived at Hub', 'is_terminal' => false, 'notify_customer' => false, 'is_first_touch' => false],
            ['key' => 'arrived_damaged', 'label' => 'Arrived Damaged', 'is_terminal' => false, 'notify_customer' => true, 'is_first_touch' => false],
            ['key' => 'missing', 'label' => 'Missing', 'is_terminal' => false, 'notify_customer' => true, 'is_first_touch' => false],
            ['key' => 'out_for_delivery', 'label' => 'Out for Delivery', 'is_terminal' => false, 'notify_customer' => true, 'is_first_touch' => false],
            ['key' => 'delivered', 'label' => 'Delivered', 'is_terminal' => true, 'notify_customer' => true, 'is_first_touch' => false],
            ['key' => 'exception', 'label' => 'Exception', 'is_terminal' => false, 'notify_customer' => true, 'is_first_touch' => false],
            ['key' => 'returned', 'label' => 'Returned', 'is_terminal' => true, 'notify_customer' => true, 'is_first_touch' => false],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'is_terminal' => true, 'notify_customer' => true, 'is_first_touch' => false],
        ];

        foreach ($defaults as $index => $status) {
            ScanStatus::firstOrCreate(
                ['key' => $status['key']],
                [...$status, 'sort_order' => $index]
            );
        }
    }
}
