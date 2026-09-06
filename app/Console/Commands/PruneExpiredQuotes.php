<?php

namespace App\Console\Commands;

use App\Models\Quote;
use Illuminate\Console\Command;

class PruneExpiredQuotes extends Command
{
    protected $signature = 'quotes:prune';

    protected $description = 'Delete quotes past their expiry date (used quotes are kept as shipment history, never pruned)';

    public function handle(): int
    {
        // A used quote stays forever — it's the record of what a real
        // shipment was actually priced at, not a stale draft. Only
        // never-booked quotes are pruned once past expires_at.
        $count = Quote::where('status', '!=', 'used')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Pruned {$count} expired quote(s).");

        return self::SUCCESS;
    }
}
