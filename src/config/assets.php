<?php

/*
 * Game asset policy configuration — specs/18 §2, specs/10 §11.
 *
 * Clash of Clans assets are used only to identify game content, unmodified, and are served
 * byte-for-byte from the `game/` prefix that never touches the media pipeline. Every value the
 * resolver, the `<x-game.asset>` component and the pack commands need is here so the whole
 * category is one config surface: a kill switch, a pack location and an origin.
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

    // Public, cookieless CDN origin — the same origin as public media. Image resizing/optimisation
    // must be OFF for the `game/` prefix at the CDN (specs/10 §11.3); that is edge config, verified
    // in staging, not something the app can assert locally.
    'cdn_url' => env('AWS_URL'),

    // The committed pack: the curated files plus the manifest the resolver reads at runtime (no
    // per-request bucket listing). Keys in the manifest are paths relative to this directory.
    'pack_path' => resource_path('game-assets'),

    // URLs carry `?v={first n chars of the file's SHA-256}`, so a replaced file gets a new URL and
    // long-lived immutable caching stays safe without versioned prefixes (specs/10 §11.3).
    'cache_bust_length' => 12,
    'cache_control' => 'public, max-age=31536000, immutable',

    // Real MIME allowlist for pack uploads — game art is delivered as raster images, byte-exact.
    'allowed_mimes' => ['image/png', 'image/webp', 'image/jpeg'],

    // Pack sub-directory → manifest category + kind. Every troop, hero, spell, pet, siege machine,
    // equipment and guardian icon shares the `unit` lookup namespace, matching the API's names.
    'directories' => [
        'units' => ['category' => 'unit', 'kind' => 'troop'],
        'heroes' => ['category' => 'unit', 'kind' => 'hero'],
        'spells' => ['category' => 'unit', 'kind' => 'spell'],
        'pets' => ['category' => 'unit', 'kind' => 'pet'],
        'machines' => ['category' => 'unit', 'kind' => 'siege'],
        'equipments' => ['category' => 'unit', 'kind' => 'equipment'],
        'guardians' => ['category' => 'unit', 'kind' => 'guardian'],
        'townhalls' => ['category' => 'townhall', 'kind' => 'townhall'],
        'leagues' => ['category' => 'league', 'kind' => 'league'],
    ],

    // Heroes equipment
    'heroes_equipments' => [
        'barbarian_king' => [
            'barbarian-puppet',
            'rage-vial',
            'earthquake-boots',
            'vampstache',
            'giant-gauntlet',
            'spiky-ball',
            'snake-bracelet',
            'stick-horse'
        ],
        'archer-queen' => [
            'archer-puppet',
            'invisibility-vial',
            'giant-arrow',
            'healer-puppet',
            'frozen-arrow',
            'magic-mirror',
            'action-figure',
            'monolith-arrow'
        ],
        'minion-prince' => [
            'henchmen-puppet',
            'dark-orb',
            'metal-pants',
            'noble-iron',
            'meteor-staff',
            'dark-crown'
        ],
        'grand-warden' => [
             'eternal tome',
            'life-gem',
            'rage-gem',
            'healing-tome',
            'heroic torch',
            'fireball',
            'lavaloon-puppet'
        ],
        'royal-champion' => [
            'seeking-shield',
            'royal-gem',
            'hog-rider-puppet',
            'haste-vial',
            'rocket-spear',
            'electro-boots',
            'frost-flake'
        ],
        'dragon-duke' => [
            'fire-heart',
            'flame-blower',
            'stun-blaster',
            'electro-fangs',
            'rocket-backpack',
            'revenge-deck'
        ]
    ],

    'heroes' => [
        'barbarian_king',
        'archer-queen',
        'minion-prince',
        'grand-warden',
        'royal-champion',
        'dragon-duke'
    ],

    'units' => [
        'elixir' => [
            'barbarian',
            'archer',
            'giant',
            'goblin',
            'wall-breaker',
            'balloon',
            'wizard',
            'healer',
            'dragon',
            'pekka',
            'baby-dragon',
            'miner',
            'electro-dragon',
            'yeti',
            'dragon-rider',
            'electro-titan',
            'root-rider',
            'thrower',
            'meteor-golem'
        ],
        'dark-elixir' => [
            'minion',
            'hog-rider',
            'valkyrie',
            'golem',
            'witch',
            'lava-hound',
            'bowler',
            'ice-golem',
            'headhunter',
            'apprentice-warden',
            'druid',
            'furnace',
            'ruin-witch'
        ],
    ],

    'spells' => [
        'elixir' => [
            'lightning',
            'healing',
            'rage',
            'jump',
            'freeze',
            'clone',
            'invisibility',
            'recall',
            'revive',
            'totem'
        ],
        'dark-elixir' => [
            'poison',
            'earthquake',
            'haste',
            'skeleton',
            'bat',
            'overgrowth',
            'ice-block',
            'angry'
        ],
    ],

    'pets' => [
        'lassi',
        'electro-owl',
        'mighty-yak',
        'unicorn',
        'frosty',
        'diggy',
        'poison-lizard',
        'phoenix',
        'spirit-fox',
        'angry-jelly',
        'sneezy',
        'greedy-raven'
    ],

    'siege-machines' => [
        'wall-wrecker',
        'battle-blimp',
        'stone-slammer',
        'siege-barracks',
        'log-launcher',
        'flame-flinger',
        'battle-drill',
        'troop-launcher',
        'sky-wagon'
    ],

    'guardians' => [
        'longshot',
        'smasher',
        'logger'
    ]
];
