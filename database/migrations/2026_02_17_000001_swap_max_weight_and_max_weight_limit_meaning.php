<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Swaps which concept each field represents, per explicit
     * correction:
     *   max_weight       was the band's matching boundary, now the
     *                     overage reference (base charge covers up to
     *                     here, extra kg beyond this is billed)
     *   max_weight_limit was the overage reference, now the band's
     *                     matching boundary (heavier shipments match a
     *                     different rate)
     *
     * Column names are unchanged — only which one PricingEngine/
     * validation/CSV treat as "the boundary" vs "the overage point"
     * changes, in code. Existing rows are swapped explicitly here so
     * every tariff calculates exactly the same as before this
     * migration — only the field that now represents each concept
     * changes, not the actual numbers a shipment is billed.
     *
     * Deliberately NOT a single `SET max_weight = max_weight_limit,
     * max_weight_limit = max_weight` — MySQL's evaluation order for
     * multiple column assignments in one UPDATE isn't something worth
     * trusting for financial data; each row is swapped explicitly in
     * PHP using values read before any write, so there's no ambiguity.
     */
    public function up(): void
    {
        foreach (['standard_billing_tariffs', 'origin_destination_tariffs'] as $table) {
            DB::table($table)->select('id', 'max_weight', 'max_weight_limit')->orderBy('id')
                ->chunkById(200, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update([
                            'max_weight' => $row->max_weight_limit,
                            'max_weight_limit' => $row->max_weight,
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach (['standard_billing_tariffs', 'origin_destination_tariffs'] as $table) {
            DB::table($table)->select('id', 'max_weight', 'max_weight_limit')->orderBy('id')
                ->chunkById(200, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update([
                            'max_weight' => $row->max_weight_limit,
                            'max_weight_limit' => $row->max_weight,
                        ]);
                    }
                });
        }
    }
};
