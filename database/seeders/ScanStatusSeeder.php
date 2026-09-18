<?php

namespace Database\Seeders;

use App\Models\ScanStatus;
use Illuminate\Database\Seeder;

class ScanStatusSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'booked', 'label' => 'Booked', 'is_terminal' => false, 'notify_customer' => true],
            ['key' => 'picked_up', 'label' => 'Picked Up', 'is_terminal' => false, 'notify_customer' => false],
            ['key' => 'in_transit', 'label' => 'In Transit', 'is_terminal' => false, 'notify_customer' => false],
            ['key' => 'transloaded', 'label' => 'Transloaded', 'is_terminal' => false, 'notify_customer' => false],
            ['key' => 'arrived_at_hub', 'label' => 'Arrived at Hub', 'is_terminal' => false, 'notify_customer' => false],
            ['key' => 'arrived_damaged', 'label' => 'Arrived Damaged', 'is_terminal' => false, 'notify_customer' => true],
            ['key' => 'missing', 'label' => 'Missing', 'is_terminal' => false, 'notify_customer' => true],
            ['key' => 'out_for_delivery', 'label' => 'Out for Delivery', 'is_terminal' => false, 'notify_customer' => true],
            ['key' => 'delivered', 'label' => 'Delivered', 'is_terminal' => true, 'notify_customer' => true],
            ['key' => 'exception', 'label' => 'Exception', 'is_terminal' => false, 'notify_customer' => true],
            ['key' => 'returned', 'label' => 'Returned', 'is_terminal' => true, 'notify_customer' => true],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'is_terminal' => true, 'notify_customer' => true],
        ];

        foreach ($defaults as $index => $status) {
            ScanStatus::firstOrCreate(
                ['key' => $status['key']],
                [...$status, 'sort_order' => $index]
            );
        }
    }
}
