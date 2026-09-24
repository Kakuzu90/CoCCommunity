<?php

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

it('renders the home landing inside the app shell', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Skip to content')
        ->assertSee('Clash')
        ->assertSee("Prove it's your base.", false)
        ->assertSee('id="content"', false)
        ->assertSee('ui-icon-home', false); // sprite is present
});

it('shows every primary navigation destination', function () {
    $html = $this->get('/')->assertOk();

    foreach (['Home', 'Bases', 'Recruit', 'Search'] as $label) {
        $html->assertSee($label);
    }
    foreach (['bases', 'recruit', 'search'] as $section) {
        $html->assertSee(route($section === 'bases' ? 'bases.index' : ($section === 'recruit' ? 'recruit.index' : 'search')), false);
    }
});

it('carries the required, linked Fan Content disclaimer on every page', function () {
    foreach (['/', '/bases', '/search'] as $path) {
        $this->get($path)
            ->assertOk()
            ->assertSee('This material is unofficial and is not endorsed by Supercell.')
            ->assertSee(config('navigation.fan_content_policy_url'), false);
    }
});

it('resolves every section route through the shell', function () {
    foreach (['bases.index', 'recruit.index', 'search', 'login', 'register'] as $name) {
        $this->get(route($name))
            ->assertOk()
            ->assertSee('Back to home')
            ->assertSee('This material is unofficial and is not endorsed by Supercell.');
    }
});

it('marks the current section as the active nav item', function () {
    $this->get('/bases')
        ->assertOk()
        ->assertSee('aria-current="page"', false);
});

it('shows guest authentication actions when signed out', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Sign up')
        ->assertSee('Create your account')
        ->assertSee(route('register'), false);
});

it('swaps guest actions for the account menu when signed in', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Account')
        ->assertSee('Notifications')
        ->assertDontSee('Create your account');
});
