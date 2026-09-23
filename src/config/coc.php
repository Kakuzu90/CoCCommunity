<?php

declare(strict_types=1);

return [

    // Which ClashClient implementation to resolve. Defaults to the live HTTP
    // adapter when a token is configured, otherwise the in-memory fake so local
    // and CI environments never reach Supercell.
    'driver' => env('COC_CLIENT', env('COC_API_TOKEN') ? 'http' : 'fake'),

    'base_url' => env('COC_API_BASE_URL', 'https://api.clashofclans.com/v1'),

    // IP-bound Supercell API token. Kept as a secret; never logged.
    'token' => env('COC_API_TOKEN'),

    'timeout' => (int) env('COC_HTTP_TIMEOUT', 10),

    // Retries for transient failures (connection errors / 5xx) inside one call.
    'retries' => (int) env('COC_HTTP_RETRIES', 3),

    'cache' => [
        // Brief TTL (seconds) to collapse duplicate player lookups within the
        // rate window. Snapshots — not this cache — are the source of truth.
        'player_ttl' => (int) env('COC_PLAYER_CACHE_TTL', 300),
    ],

    'sync' => [
        // A verified account is refreshed once its snapshot is older than this.
        'stale_after_hours' => (int) env('COC_SYNC_STALE_HOURS', 12),
        // Max accounts enqueued per scheduled refresh pass.
        'batch' => (int) env('COC_SYNC_BATCH', 200),
    ],

    // Seed data for the fake client in local dev, so the linked web and worker
    // processes agree and the verify flow is demoable without live API access.
    // Each entry is a normalised tag => API-shaped payload plus a 'token'.
    'fake' => [
        'players' => [
            '#2P0YQRL8V' => [
                'token' => 'TEST-TOKEN',
                'name' => 'NightWitch',
                'townHallLevel' => 17,
                'expLevel' => 250,
                'trophies' => 6000,
                'bestTrophies' => 6200,
                'warStars' => 1500,
                'league' => ['name' => 'Legend League'],
                'clan' => ['tag' => '#CLANTAG', 'name' => 'Bicol Warriors'],
                'role' => 'coLeader',
            ],
        ],
    ],
];
