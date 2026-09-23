<?php

declare(strict_types=1);

$modules = array_map('basename', glob(dirname(__DIR__, 2).'/app/Modules/*', GLOB_ONLYDIR) ?: []);

foreach ($modules as $module) {
    foreach (array_diff($modules, [$module]) as $other) {
        arch($module.' cannot import '.$other.' internals')
            ->expect('App\\Modules\\'.$module)
            ->not->toUse(array_map(
                fn (string $layer): string => 'App\\Modules\\'.$other.'\\'.$layer,
                ['Models', 'Actions', 'Http', 'Jobs', 'Listeners', 'Policies'],
            ));
    }
}

arch('module classes use strict types')
    ->expect('App\\Modules')->toUseStrictTypes();
