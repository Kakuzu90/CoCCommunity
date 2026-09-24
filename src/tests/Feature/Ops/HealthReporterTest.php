<?php

use App\Support\Health\HealthReporter;
use App\Support\Health\HealthStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('returns http 200 and an ok database check on a healthy system', function () {
    $summary = app(HealthReporter::class)->summary();

    expect($summary['http'])->toBe(200)
        ->and($summary['checks']['database']['status'])->toBe('ok');
});

it('flags the queue as degraded when the backlog exceeds the threshold', function () {
    config(['health.queue_backlog_degraded' => 0]);
    DB::table('jobs')->insert([
        'queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null,
        'available_at' => now()->timestamp, 'created_at' => now()->timestamp,
    ]);

    $check = app(HealthReporter::class)->queue();

    expect($check->status)->toBe(HealthStatus::Degraded)
        ->and($check->meta['depth'])->toBe(1);
});

it('treats the scheduler as alive once it has written a recent heartbeat', function () {
    cache()->put(config('health.cache.scheduler'), now()->toIso8601String(), 600);

    expect(app(HealthReporter::class)->queue()->status)->toBe(HealthStatus::Ok);
});
