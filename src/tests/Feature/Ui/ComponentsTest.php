<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders the gallery outside production', function () {
    $this->withoutVite();
    $this->get('/dev/components')->assertOk()
        ->assertSee('A common language.')
        ->assertSee('Skip to content')
        ->assertSee('<dialog', false)
        ->assertSee('noindex,nofollow', false);
});

it('hides the gallery in production even when its route is registered', function () {
    $this->app['env'] = 'production';
    $this->get('/dev/components')->assertNotFound();
});

it('defaults buttons to non-submitting and locks loading controls', function () {
    $html = Blade::render('<x-ui.button :loading="true">Save draft</x-ui.button>');
    expect($html)->toContain('type="button"', 'disabled', 'aria-busy="true"', 'Save draft', 'Loading');
});

it('requires accessible names for icon-only buttons', function () {
    expect(fn () => Blade::render('<x-ui.button :icon-only="true"><x-ui.icon name="plus" /></x-ui.button>'))
        ->toThrow(ViewException::class, 'Icon buttons require aria-label');
});

it('links field labels, hints, caller descriptions and validation errors', function () {
    $html = Blade::render('<x-ui.input id="tag" label="Player tag" hint="Find this in-game" error="Invalid tag" aria-describedby="extra" wire:model="tag" />');
    expect($html)->toContain('for="tag"', 'id="tag"', 'aria-invalid="true"', 'aria-describedby="tag-hint tag-error extra"', 'role="alert"', 'wire:model="tag"');
});

it('renders stable avatar geometry and accessible fallback text', function () {
    $html = Blade::render('<x-ui.avatar name="Alex River" src="/avatars/alex.webp" :size="64" :verified="true" />');
    expect($html)->toContain('width="64"', 'height="64"', 'loading="lazy"', 'Alex River, verified', 'AR');
});
