<?php

use Symfony\Component\Finder\Finder;

it('uses semantic color tokens in design system templates', function () {
    $files = Finder::create()->files()->name('*.blade.php')->in([
        dirname(__DIR__, 2).'/resources/views/components',
        dirname(__DIR__, 2).'/resources/views/dev',
        dirname(__DIR__, 2).'/resources/views/pages',
    ]);
    foreach ($files as $file) {
        expect($file->getContents())
            ->not->toMatch('/(?:bg|text|border|ring|fill|stroke|from|to|via)-\[(?:#|rgba?\(|hsla?\(|oklch\()/i')
            ->not->toMatch('/(?:color|background|fill|stroke)\s*:\s*(?:#[0-9a-f]{3,8}\b|rgba?\(|hsla?\()/i')
            ->not->toMatch('/(?:bg|text|border|ring)-(?:red|green|blue|yellow|purple|gray|slate|zinc|neutral|amber)-\d+/');
    }
});
