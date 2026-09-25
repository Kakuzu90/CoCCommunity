<?php

/*
 * Switches for surfaces whose backing feature ships in a later phase (specs/25). A profile stat or tab
 * that counts something nobody can do yet is filler, so it stays hidden until its feature is on.
 */
return [
    // Phase 3 "Publishing + composer" turns this on; it reveals base stats and the Bases tab on profiles.
    'base_publishing' => (bool) env('FEATURE_BASE_PUBLISHING', false),
];
