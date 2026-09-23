<?php

declare(strict_types=1);

test('modules do not reference another modules eloquent models', function (): void {
    $directories = glob(app_path('Modules/*'), GLOB_ONLYDIR) ?: [];
    expect($directories)->not->toBeEmpty();

    foreach ($directories as $directory) {
        $module = basename($directory);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                preg_match_all('/App\\\\Modules\\\\(\w+)\\\\Models\\\\/', file_get_contents($file->getPathname()), $matches);

                foreach ($matches[1] as $referencedModule) {
                    expect($referencedModule, $file->getPathname())->toBe($module);
                }
            }
        }
    }
});
