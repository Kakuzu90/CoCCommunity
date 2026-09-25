<?php

/*
 * Media pipeline configuration — specs/10-media-storage.md.
 * Every limit, window and variant lives here so nothing is a magic number in code,
 * and swapping MinIO → R2 is an .env change with no code edit (specs/10 §2.1).
 */

$mb = fn (int $n): int => $n * 1024 * 1024;

return [
    // Filesystem disk (config/filesystems.php). Local dev points this disk at MinIO; prod at R2.
    'disk' => env('MEDIA_DISK', 'r2'),

    // Local MinIO signs URLs against an in-network host the browser cannot resolve; rewrite it.
    // Empty in staging/production (specs/10 §2.1).
    'presign_host' => env('MEDIA_PRESIGN_HOST'),

    // Public CDN origin (cookieless). Locally this is the MinIO public URL.
    'cdn_url' => env('AWS_URL'),

    'prefixes' => [
        'quarantine' => 'quarantine',   // raw upload lands here, never served
        'public' => 'public',
        'private' => 'private',
    ],

    'intent' => [
        'presign_ttl' => 300,           // seconds a presigned PUT stays valid (specs/10 §3)
        'expiry_hours' => 24,           // unattached pending/uploaded rows expire and are swept
        'size_tolerance' => 1024,       // bytes the real object may differ from the declared size
    ],

    // Decompression-bomb guard: dimensions are read from the header before any decode (specs/23 §4).
    'limits' => [
        'image_max_width' => 6000,
        'image_max_height' => 6000,
        'image_min_width' => 200,
        'image_min_height' => 200,
    ],

    'webp_quality' => 82,

    // Per-user total storage (recomputed nightly into user_stats — a later task owns the job).
    'user_soft_cap' => $mb(500),
    'user_soft_cap_warn' => 0.8,

    /*
     * Collections active in this phase are image-only. `base_video` and `evidence` are declared in
     * the MediaCollection enum for schema completeness but are enabled in their own phases
     * (video → Phase 3, evidence → moderation). Intent only accepts the keys listed here.
     */
    'collections' => [
        'avatar' => [
            'kind' => 'image',
            'visibility' => 'public',
            'max_size' => $mb(2),
            'declared_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'variants' => [
                'full' => ['width' => 512, 'height' => 512, 'crop' => true],
                'card' => ['width' => 128, 'height' => 128, 'crop' => true],
                'thumb' => ['width' => 48, 'height' => 48, 'crop' => true],
            ],
        ],
        'account_image' => [
            'kind' => 'image',
            'visibility' => 'public',
            'max_size' => $mb(5),
            'declared_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'variants' => [
                'full' => ['width' => 1600],
                'card' => ['width' => 800],
                'thumb' => ['width' => 320],
            ],
        ],
        'base_screenshot' => [
            'kind' => 'image',
            'visibility' => 'public',
            'max_size' => $mb(5),
            'declared_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'variants' => [
                'full' => ['width' => 1600],
                'card' => ['width' => 800],
                'thumb' => ['width' => 320],
            ],
        ],
        'portfolio' => [
            'kind' => 'image',
            'visibility' => 'public',
            'max_size' => $mb(5),
            'declared_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'variants' => [
                'card' => ['width' => 800],
                'thumb' => ['width' => 320],
            ],
        ],
        // Ownership-dispute evidence (specs/13 §5). Private: staff-only, never public, each access
        // audit-logged. Screenshots from inside the game — kept small, stored behind the private prefix.
        'evidence' => [
            'kind' => 'image',
            'visibility' => 'private',
            'max_size' => $mb(5),
            'declared_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'variants' => [
                'full' => ['width' => 1600],
                'thumb' => ['width' => 320],
            ],
        ],
    ],
];
