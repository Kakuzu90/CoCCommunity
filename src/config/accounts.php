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
];
