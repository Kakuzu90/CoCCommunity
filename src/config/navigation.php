<?php

/*
 * Primary navigation is data, not markup, so the top bar, desktop sidebar and
 * mobile bottom tabs all render from one source (specs/18-design-system.md §5).
 *
 *  - route  : named route the item links to
 *  - active : pattern for request()->routeIs() to mark the current section
 *  - icon   : platform icon (resources/views/components/ui/icon.blade.php); never a game asset
 */
return [
    'primary' => [
        ['key' => 'home', 'label' => 'Home', 'icon' => 'home', 'route' => 'home', 'active' => 'home'],
        ['key' => 'bases', 'label' => 'Bases', 'icon' => 'layers', 'route' => 'bases.index', 'active' => 'bases.*'],
        ['key' => 'recruit', 'label' => 'Recruit', 'icon' => 'shield', 'route' => 'recruit.index', 'active' => 'recruit.*'],
        ['key' => 'search', 'label' => 'Search', 'icon' => 'search', 'route' => 'search', 'active' => 'search'],
    ],

    // Where the required Supercell Fan Content disclaimer links (specs/18-design-system.md §2.1).
    'fan_content_policy_url' => 'https://supercell.com/en/fan-content-policy/',

    /*
     * Admin left nav (specs/18-design-system.md §6 "/admin/*"). The full surface is Dashboard ·
     * Reports · Disputes · Users · Content · Media · Marketplace · Logs; Admin v1 ships Dashboard,
     * Users and Logs. The rest are listed as `enabled => false` so the information architecture is
     * visible and honest — a disabled item, not a dead link — until its phase builds it.
     */
    'admin' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'enabled' => true],
        ['key' => 'reports', 'label' => 'Reports', 'icon' => 'flag', 'route' => null, 'active' => 'admin.reports.*', 'enabled' => false],
        ['key' => 'disputes', 'label' => 'Disputes', 'icon' => 'scale', 'route' => 'admin.disputes.index', 'active' => 'admin.disputes.*', 'enabled' => true],
        ['key' => 'users', 'label' => 'Users', 'icon' => 'user', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'enabled' => true],
        ['key' => 'content', 'label' => 'Content', 'icon' => 'document', 'route' => null, 'active' => 'admin.content.*', 'enabled' => false],
        ['key' => 'media', 'label' => 'Media', 'icon' => 'image', 'route' => null, 'active' => 'admin.media.*', 'enabled' => false],
        ['key' => 'marketplace', 'label' => 'Marketplace', 'icon' => 'store', 'route' => null, 'active' => 'admin.marketplace.*', 'enabled' => false],
        ['key' => 'logs', 'label' => 'Logs', 'icon' => 'list', 'route' => 'admin.logs.index', 'active' => 'admin.logs.*', 'enabled' => true],
    ],
];
