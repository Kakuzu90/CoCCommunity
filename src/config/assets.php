<?php

/*
 * Game asset policy configuration — specs/18 §2, specs/10 §11.
 *
 * Clash of Clans assets are used only to identify game content, unmodified, and are served
 * byte-for-byte from a versioned `game/` prefix that never touches the media pipeline. Every
 * value the resolver, the `<x-game.asset>` component and the pack commands need is here so the
 * whole category is one config surface: a kill switch, a pack version and an origin.
 */
return [
    // Kill switch. False → the resolver returns our own placeholder everywhere and no game asset
    // is served. This is condition 7 of specs/18 §2.1 ("fan content permission is revocable") in
    // executable form.
    'enabled' => (bool) env('GAME_ASSETS_ENABLED', true),

    // The `game/` prefix lives in the same S3-compatible bucket as media (MinIO locally, R2 in
    // prod), chosen by the same env as the media disk so nothing here names a provider.
    'disk' => env('MEDIA_DISK', 'r2'),

    // Bucket prefix for the curated catalogue. Kept separate so every sweeper, quota and reconcile
    // job ignores it by rule, not by accident (specs/10 §11.3).
    'prefix' => 'game',

    // Active pack version. Activation and rollback are a one-line change here; the previous version
    // stays in the bucket, immutable (specs/10 §11.2).
    'pack_version' => (int) env('GAME_ASSET_PACK_VERSION', 1),

    // Public, cookieless CDN origin — the same origin as public media. Image resizing/optimisation
    // must be OFF for the `game/` prefix at the CDN (specs/10 §11.3); that is edge config, verified
    // in staging, not something the app can assert locally.
    'cdn_url' => env('AWS_URL'),

    // The committed manifest the resolver reads at runtime (no per-request bucket listing). The
    // pack for version {n} lives at {manifest_path}/{n}/manifest.json (specs/10 §11.2).
    'manifest_path' => resource_path('game-assets'),

    // Long-lived immutable caching is safe because a version prefix is never edited in place.
    'cache_control' => 'public, max-age=31536000, immutable',

    // Real MIME allowlist for pack uploads — game art is delivered as raster images, byte-exact.
    'allowed_mimes' => ['image/png', 'image/webp', 'image/jpeg'],

    // Manifest category → prefix sub-directory under game/{version}/.
    'categories' => [
        'unit' => 'units',
        'townhall' => 'townhalls',
        'league' => 'leagues',
    ],
];
