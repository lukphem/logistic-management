<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Quotes carry their own expires_at set at generation time (see
// QuoteController::store), so this just needs to run once a day —
// nothing time-sensitive depends on catching an expiry within the hour.
Schedule::command('quotes:prune')->daily();

// A reference is only requeried once at least 10 minutes old (see
// the command itself), so running this every 10 minutes is frequent
// enough to catch a missed webhook/callback quickly without ever
// querying a still-in-progress checkout.
Schedule::command('payments:requery-pending')->everyTenMinutes();
