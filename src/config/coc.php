<?php

return [
    // FR-COC-2: bounds exclude the leading #. These describe tag syntax, not existence.
    'tags' => [
        'min_length' => 3,
        'max_length' => 12,
        'alphabet' => '0289PYLQGRJCUV',
    ],

    // No maximum: future Town Hall levels must work without a deployment (spec 23 §5).
    // Publishing's minimum is a separate Bases policy, not an account identity constraint.
    'th_min_level' => 1,

    /*
     * Which CocApiClient the container binds (specs/09 §1). 'fake' returns scripted DTOs with no
     * network — the default everywhere until a real IP-bound key exists (CLAUDE.md local-first).
     * 'http' wires Cached(Throttled(Http)) against the live API.
     */
    'driver' => env('COC_DRIVER', 'fake'),

    'base_url' => env('COC_API_BASE_URL', 'https://api.clashofclans.com/v1'),

    /*
     * The key pool (specs/09 §3). Keys are IP-bound, so production runs one per stable egress IP plus
     * spares, comma-separated. Never logged, never sent to the browser, never put in a job payload.
     */
    'tokens' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('COC_API_TOKEN', '')),
    ))),

    // Connect / total request timeouts in seconds (specs/09 §7 network row). A timeout is a failure.
    'timeouts' => [
        'connect' => (int) env('COC_TIMEOUT_CONNECT', 5),
        'total' => (int) env('COC_TIMEOUT_TOTAL', 10),
    ],

    // Cache TTLs in seconds (specs/09 §5). Caching is the outermost decorator so a hit costs no
    // rate-limit budget. Invalidation is by explicit key deletion on manual refresh.
    'cache' => [
        'player_ttl' => (int) env('COC_CACHE_PLAYER_TTL', 300),        // 5 min
        'negative_ttl' => (int) env('COC_CACHE_NEGATIVE_TTL', 600),    // 10 min — stops typo retry storms
        'stale_ttl' => (int) env('COC_CACHE_STALE_TTL', 86400),        // 24 h — stale-while-error fallback
    ],

    // Self-imposed rate budget, kept well below any observed ceiling (specs/09 §4). 429 is normal,
    // not an error. Interactive lookups reserve a share so a sync storm never starves a user.
    'rate' => [
        'global_per_second' => (int) env('COC_RATE_GLOBAL_PER_SECOND', 10),
        'global_per_minute' => (int) env('COC_RATE_GLOBAL_PER_MINUTE', 500),
        'per_key_per_second' => (int) env('COC_RATE_PER_KEY_PER_SECOND', 5),
        'interactive_share' => (float) env('COC_RATE_INTERACTIVE_SHARE', 0.30),
    ],

    // Circuit breaker (specs/09 §7). Opens on a run of failures or a high error rate; while open no
    // outbound call is made and everything serves from cache/snapshots.
    'circuit' => [
        'threshold' => (int) env('COC_CIRCUIT_THRESHOLD', 10),          // consecutive failures
        'error_rate' => (float) env('COC_CIRCUIT_ERROR_RATE', 0.50),    // over the window
        'window' => (int) env('COC_CIRCUIT_WINDOW', 120),               // seconds
        'min_samples' => (int) env('COC_CIRCUIT_MIN_SAMPLES', 20),
        'probe_interval' => (int) env('COC_CIRCUIT_PROBE_INTERVAL', 60), // half-open probe cadence
        'maintenance_default' => (int) env('COC_CIRCUIT_MAINTENANCE_DEFAULT', 1800), // fallback if no end time
    ],

    // Attach + token verification limits, per user (specs/13 §3 step 2, specs/09 §9). Tokens are
    // short-lived, so verification must stay immediate; the caps only blunt brute-force and abuse.
    'attach' => [
        'attempts_per_hour' => (int) env('COC_ATTACH_ATTEMPTS_PER_HOUR', 5),
        'verify_attempts_per_hour' => (int) env('COC_VERIFY_ATTEMPTS_PER_HOUR', 5),
    ],

    // Ownership dispute guardrails (specs/13 §5). Disputes are the slow, manual, evidence-based path
    // for the honest owner who cannot produce a token; the limits blunt griefing without blocking a
    // genuine claim. Windows are days.
    'dispute' => [
        'max_open_per_user' => (int) env('COC_DISPUTE_MAX_OPEN', 2),
        'holder_response_days' => (int) env('COC_DISPUTE_HOLDER_RESPONSE_DAYS', 7),
        'denied_bar_count' => (int) env('COC_DISPUTE_DENIED_BAR_COUNT', 2),
        'denied_bar_days' => (int) env('COC_DISPUTE_DENIED_BAR_DAYS', 90),
        'auto_withdraw_days' => (int) env('COC_DISPUTE_AUTO_WITHDRAW_DAYS', 30),
        'reason_max' => (int) env('COC_DISPUTE_REASON_MAX', 1000),
        'max_evidence' => (int) env('COC_DISPUTE_MAX_EVIDENCE', 3),
    ],

    // Key pool health (specs/09 §3). A key marked unhealthy (bad key or IP binding) stays out of
    // rotation for this long before a probe may try it again — long enough to page an operator.
    'keys' => [
        'unhealthy_ttl' => (int) env('COC_KEY_UNHEALTHY_TTL', 3600),
    ],

    // coc_api_requests observability log (specs/09 §4). Pruned by coc:prune-api-requests.
    'request_log' => [
        'retention_days' => (int) env('COC_REQUEST_LOG_RETENTION_DAYS', 7),
    ],

    'sync' => [
        'queue' => env('COC_SYNC_QUEUE', 'sync'),
        'tries' => (int) env('COC_SYNC_TRIES', 3),
        'backoff' => [
            (int) env('COC_SYNC_BACKOFF_FIRST', 60),
            (int) env('COC_SYNC_BACKOFF_SECOND', 300),
            (int) env('COC_SYNC_BACKOFF_THIRD', 900),
        ],
        'overlap_seconds' => (int) env('COC_SYNC_OVERLAP_SECONDS', 65),
        'batch_size' => (int) env('COC_SYNC_BATCH_SIZE', 200),
        'scheduler_seconds' => (int) env('COC_SYNC_SCHEDULER_SECONDS', 300),
        'unique_seconds' => (int) env('COC_SYNC_UNIQUE_SECONDS', 60),
        'retry_seconds' => (int) env('COC_SYNC_RETRY_SECONDS', 60),
        'not_found_stale_after' => (int) env('COC_SYNC_NOT_FOUND_STALE_AFTER', 3),
        'freeze_after' => (int) env('COC_SYNC_FREEZE_AFTER', 5),
        'hot_owner_days' => (int) env('COC_SYNC_HOT_OWNER_DAYS', 7),
        'warm_owner_days' => (int) env('COC_SYNC_WARM_OWNER_DAYS', 30),
        'hot_view_hours' => (int) env('COC_SYNC_HOT_VIEW_HOURS', 24),
        'manual_cooldown_seconds' => (int) env('COC_REFRESH_COOLDOWN_SECONDS', 600),
        'manual_timeout_seconds' => (int) env('COC_REFRESH_TIMEOUT_SECONDS', 3),
        'snapshot_full_days' => (int) env('COC_SNAPSHOT_FULL_DAYS', 90),
        'snapshot_daily_days' => (int) env('COC_SNAPSHOT_DAILY_DAYS', 365),
        'tiers' => [
            'hot' => (int) env('COC_SYNC_HOT_SECONDS', 7200),
            'warm' => (int) env('COC_SYNC_WARM_SECONDS', 43200),
            'cold' => (int) env('COC_SYNC_COLD_SECONDS', 259200),
            'frozen' => (int) env('COC_SYNC_FROZEN_SECONDS', 604800),
        ],
    ],

    /*
     * Automated key rotation via the developer portal (specs/09 §3). Disabled by default: it needs
     * portal credentials (blocked on the operator) and a stable egress IP. Off → the documented
     * manual runbook applies and the health surface alerts when the pool goes unhealthy.
     */
    // Town Hall colour tiers (specs/18 §4 ThBadge): highest TH level in each tier. Anything above the last
    // entry is the top tier, so a new TH level needs no change here unless the ramp itself moves.
    'th_tiers' => [4 => 1, 7 => 2, 10 => 3, 12 => 4, 14 => 5, 16 => 6],
    'th_top_tier' => 7,

    'key_rotation' => [
        'enabled' => (bool) env('COC_KEY_ROTATION_ENABLED', false),
        'portal_email' => env('COC_PORTAL_EMAIL'),
        'portal_password' => env('COC_PORTAL_PASSWORD'),
    ],
];
