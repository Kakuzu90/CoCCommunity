<?php

use App\Modules\Media\Jobs\ReapOrphans;
use App\Modules\PlayerAccounts\Jobs\RefreshStaleAccounts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Refresh stale verified-account snapshots, staggered within API rate limits.
Schedule::job(new RefreshStaleAccounts)->hourly();

// Delete uploads that were started but never finalised.
Schedule::job(new ReapOrphans)->hourly();
