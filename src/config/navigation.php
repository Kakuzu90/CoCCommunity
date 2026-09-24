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
];
