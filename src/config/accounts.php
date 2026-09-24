<?php

/*
 * Registration + credential policy (specs/04 §4, specs/11). Every limit and list is config so
 * nothing is a magic number in code, and the disposable/reserved lists are refreshable by ops.
 */
return [
    'username' => [
        'min' => 3,
        'max' => 20,
        // Letters, digits, underscore; must start with a letter. No leading/trailing/again-underscore spam.
        'pattern' => '/^[a-zA-Z][a-zA-Z0-9_]{2,19}$/',
        // Handles we never hand out: routes, roles and impersonation bait (specs/04 §4).
        'reserved' => [
            'admin', 'administrator', 'mod', 'moderator', 'support', 'staff', 'api', 'root',
            'system', 'clashcommons', 'clash', 'supercell', 'official', 'help', 'u', 'user',
            'base', 'bases', 'search', 'settings', 'login', 'logout', 'register', 'me', 'null',
        ],
    ],

    'password' => [
        'min' => 10,
        // HIBP k-anonymity check on register + change (specs/04 §4). Fail-open if unreachable.
        // Disabled in tests to keep them offline.
        'check_compromised' => (bool) env('AUTH_CHECK_COMPROMISED', true),
    ],

    // Bot friction on registration (specs/11): a hidden honeypot field and a minimum fill time.
    'registration' => [
        'honeypot_field' => 'company_website',
        'min_fill_seconds' => 2,
    ],

    // Committed starter blocklist; ops refresh it monthly (specs/11). One domain per line, '#' comments.
    'disposable_domains_file' => resource_path('security/disposable-email-domains.txt'),

    // Profile presentation limits and allowlists (specs/07 `profiles`). Editable fields only —
    // display_name falls back to the username when blank.
    'profile' => [
        'display_name_max' => 50,
        'bio_max' => 500,
        'languages_max' => 3,
        // Free-text language names the user types (e.g. English, Bisaya). Length-capped, not an
        // ISO code list — communities use local names the code lists do not carry.
        'language_max' => 30,
        // ISO-3166-1 alpha-2; stored upper-cased. Shape-validated server-side; the UI select scopes it.
        'country_pattern' => '/^[A-Za-z]{2}$/',
        // The only social platforms a profile can link; each value is a handle/URL, length-capped.
        'socials' => ['youtube', 'twitch', 'discord', 'x'],
        'social_max' => 100,
    ],
];
