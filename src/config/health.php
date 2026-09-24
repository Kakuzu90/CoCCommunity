<?php

/*
 * Operational health + observability configuration — specs/20 §6, specs/03 §7 (NFR-OBS-*).
 * Every threshold is a config key so nothing operational is a magic number in code.
 */
return [
    // Release tag attached to logs and reported errors (NFR-OBS-2). Set to the deploy SHA in CI.
    'release' => env('APP_RELEASE', 'dev'),

    // Cache keys the health surface reads. External reachability is written by platform:check-health
    // (every 5 min) rather than probed on every /health poll; the scheduler writes a heartbeat.
    'cache' => [
        'external' => 'health:external',
        'scheduler' => 'health:scheduler',
    ],

    // How long a cached external-check result stays fresh (seconds). Slightly over its 5-min cadence.
    'external_ttl' => 360,

    // The scheduler is considered down if it has not written a heartbeat within this many seconds.
    'scheduler_stale_after' => 300,

    // Queue depth that flips the queue check to "degraded" (specs/20 §6 alert threshold).
    'queue_backlog_degraded' => 500,

    // Failed jobs on the books that flip the queue check to "degraded".
    'failed_jobs_degraded' => 20,

    // The disk whose reachability platform:check-health probes (the media/game bucket).
    'storage_disk' => env('MEDIA_DISK', 'r2'),

    // HTTP routes excluded from per-request access logging so uptime polls do not flood the log.
    'unlogged_paths' => ['up', 'health'],
];
