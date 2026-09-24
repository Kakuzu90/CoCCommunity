<?php

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\Finder;

arch('application code has no debugging calls')
    ->expect(['dd', 'dump', 'ray'])->not->toBeUsed();

arch('environment is only read in configuration')
    ->expect('App')->not->toUse('env');

arch('domain does not depend on presentation')
    ->expect('App\Domain')->not->toUse(['App\Http', 'App\Livewire']);

it('models declare explicit fillable attributes and protect privileged fields', function () {
    $files = Finder::create()->files()->name('*.php')->in(dirname(__DIR__, 2).'/app');

    foreach ($files as $file) {
        $class = 'App\\'.str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));
        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            continue;
        }
        $reflection = new ReflectionClass($class);
        if ($reflection->isAbstract()) {
            continue;
        }
        $properties = $reflection->getDefaultProperties();
        expect($reflection->getProperty('fillable')->getDeclaringClass()->getName())->toBe($class);
        expect($properties['fillable'])->not->toBeEmpty();
        expect($properties['guarded'])->not->toBe([]);
        foreach (['role', 'status', 'user_id'] as $field) {
            expect($properties['fillable'])->not->toContain($field);
        }
    }
});

arch('enums are backed')
    ->expect('App')->enums()->toImplement(BackedEnum::class);

it('each Livewire page has a corresponding feature test', function () {
    $root = dirname(__DIR__, 2);
    $pages = $root.'/app/Livewire/Pages';
    if (! is_dir($pages)) {
        expect(is_dir($root.'/tests/Feature'))->toBeTrue();

        return;
    }

    foreach (Finder::create()->files()->name('*.php')->in($pages) as $file) {
        $test = $root.'/tests/Feature/'.substr($file->getRelativePathname(), 0, -4).'Test.php';
        expect(is_file($test))->toBeTrue('Missing feature test: '.$test);
    }
});
