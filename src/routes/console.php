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

// Weekly bucket↔database reconcile (specs/10 §9). The allowlist in StorageReconciler keeps the
// game/ prefix out of scope by construction.
Schedule::command('media:reconcile-storage')->weekly()->sundays()->at('03:20')->onOneServer();

// Weekly game asset pack integrity audit (specs/10 §9, §11.2): the bucket must still match the
// committed manifest, so "unmodified" stays demonstrable.
Schedule::command('assets:verify-pack')->weekly()->sundays()->at('03:40')->onOneServer();
