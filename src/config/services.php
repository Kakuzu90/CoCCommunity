<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'turnstile' => [
        // Cloudflare Turnstile (specs/11). Locally, Cloudflare's public always-pass TEST keys are
        // the default; production overrides via env. `enabled=false` skips verification (tests).
        'enabled' => (bool) env('TURNSTILE_ENABLED', true),
        'sitekey' => env('TURNSTILE_SITEKEY', '1x00000000000000000000AA'),
        'secret' => env('TURNSTILE_SECRET', '1x0000000000000000000000000000000AA'),
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
