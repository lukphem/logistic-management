<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The ScanStatusSeeder's use of firstOrCreate() is deliberate —
     * it never overwrites a label/sort_order a staff member has
     * customized — but that same behavior meant a "picked_up" row
     * created before is_first_touch existed never actually got that
     * flag set when the seeder ran again, since firstOrCreate() only
     * touches rows it's creating, not ones that already exist. The
     * exact real-world effect: Pickup Scan itself was rejected with
     * "hasn't been picked up or dropped off yet," on the one status
     * that's supposed to be exempt from that check.
     *
     * A real migration, not a re-run seeder, since this needs to
     * apply automatically on `php artisan migrate` — no separate
     * manual seeding step to remember, and it fixes a pre-existing
     * "picked_up" row's flag directly rather than requiring one to
     * not already exist.
     */
    public function up(): void
    {
        DB::table('scan_statuses')->where('key', 'picked_up')->update(['is_first_touch' => true]);

        if (! DB::table('scan_statuses')->where('key', 'dropped_off')->exists()) {
            $nextSortOrder = (int) (DB::table('scan_statuses')->max('sort_order') ?? 0) + 1;

            DB::table('scan_statuses')->insert([
                'key' => 'dropped_off',
                'label' => 'Dropped Off',
                'sort_order' => $nextSortOrder,
                'is_terminal' => false,
                'is_delivery_attempt' => false,
                'notify_customer' => false,
                'is_first_touch' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('scan_statuses')->where('key', 'dropped_off')->update(['is_first_touch' => true]);
        }
    }

    /**
     * Deliberately no-op — reversing this would mean deciding whether
     * to turn is_first_touch back off on a status a company may by
     * then be actively relying on, which isn't a safe assumption to
     * make automatically.
     */
    public function down(): void
    {
    }
};
