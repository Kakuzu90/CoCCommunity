<?php

use Symfony\Component\Finder\Finder;

/*
 * specs/18 §2.3: no template hardcodes a game-asset path. Every game asset reaches a view through
 * <x-game.asset> / GameAssetResolver, so removing or replacing the whole category is one class.
 * A Blade referencing `game/{version}/…` directly (an <img src>, a url(), a config-free literal)
 * would silently bypass the kill switch and the resolver — this lint fails the build if one does.
 */
it('has no hardcoded game-asset path in any Blade template', function () {
    $views = Finder::create()->files()->name('*.blade.php')->in(dirname(__DIR__, 2).'/resources/views');

    $offenders = [];
    foreach ($views as $view) {
        // Matches a real path segment (game/1/…, game/units/…, game/{version}/…), not the
        // component name `x-game.asset` or the section id `game-assets`.
        if (preg_match('#["\'(/]game/(?:\{|\d|units|townhalls|leagues)#', $view->getContents())) {
            $offenders[] = $view->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
