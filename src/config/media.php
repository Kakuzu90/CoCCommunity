<?php

declare(strict_types=1);

return [

    // Disk media objects live on. R2 in production; falls back to the app's
    // default disk (local) in dev so the pipeline runs without R2 credentials.
    'disk' => env('MEDIA_DISK', env('FILESYSTEM_DISK', 'local')),

    // Expiry for signed, private read/upload URLs.
    'signed_url_ttl' => (int) env('MEDIA_SIGNED_URL_TTL', 300),

    // Pending media older than this with no successful finalize is reaped.
    'orphan_ttl_hours' => (int) env('MEDIA_ORPHAN_TTL_HOURS', 24),

    'image' => [
        'max_dimension' => 2000, // downscale originals beyond this (px)
        'thumbnail' => 400,      // thumbnail longest edge (px)
        'quality' => 82,
    ],

    // Per-collection rules. max_size in bytes; max_files is enforced by the
    // caller (it knows the current count for the owning entity).
    'collections' => [
        'avatar' => [
            'kind' => 'image', 'max_files' => 1, 'max_size' => 2 * 1024 * 1024,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
        'account' => [
            'kind' => 'image', 'max_files' => 5, 'max_size' => 5 * 1024 * 1024,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
        'base_image' => [
            'kind' => 'image', 'max_files' => 2, 'max_size' => 5 * 1024 * 1024,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
        // Post-MVP: video is stored but not transcoded yet.
        'base_video' => [
            'kind' => 'video', 'max_files' => 1, 'max_size' => 100 * 1024 * 1024,
            'mimes' => ['video/mp4', 'video/quicktime'],
        ],
    ],
];
