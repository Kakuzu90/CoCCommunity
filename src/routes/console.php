<?php

use App\Domain\Media\Jobs\SweepOrphanMediaJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Media orphan sweep (specs/20 §schedule: hourly :20). The media:sweep-orphans command is the
// manual/dry-run equivalent.
Schedule::job(new SweepOrphanMediaJob)->hourlyAt(20)->onOneServer();
