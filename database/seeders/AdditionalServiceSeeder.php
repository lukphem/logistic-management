<?php

namespace Database\Seeders;

use App\Models\AdditionalService;
use Illuminate\Database\Seeder;

class AdditionalServiceSeeder extends Seeder
{
    /**
     * Packaging and Acknowledgement are the two protected, built-in
     * Additional Services every business using this system has
     * (AdditionalService::isProtected() locks their name/deletion) —
     * but nothing previously created them. keyed on `kind` and using
     * updateOrCreate so this is safe to run again: it backfills the
     * name on an existing row rather than creating a duplicate, which
     * also fixes a kind='packaging' row that already exists with no
     * name set.
     *
     * Their priced options aren't seeded here — Packaging's are
     * business-specific (added via the normal Additional Services
     * form), and Acknowledgement's single option is created the first
     * time its own settings form (Standard Billing → Acknowledgement)
     * is saved, via AdditionalServiceController::updateAcknowledgement().
     */
    public function run(): void
    {
        AdditionalService::updateOrCreate(
            ['kind' => 'packaging'],
            ['name' => 'Packaging', 'is_active' => true]
        );

        AdditionalService::updateOrCreate(
            ['kind' => 'acknowledgement'],
            ['name' => 'Acknowledgement', 'is_active' => true]
        );
    }
}
