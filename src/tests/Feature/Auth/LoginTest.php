<?php

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('logs in with valid credentials and records the login', function () {
    $user = User::factory()->create(['password' => Hash::make('a-strong-passphrase')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'a-strong-passphrase'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->last_login_at)->not->toBeNull()
        ->and($user->last_login_ip_hash)->not->toBeNull();
});

it('accepts the email case-insensitively', function () {
    $user = User::factory()->create(['email' => 'player@gmail.com', 'password' => Hash::make('a-strong-passphrase')]);

    $this->post('/login', ['email' => 'PLAYER@Gmail.com', 'password' => 'a-strong-passphrase'])
        ->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password with a generic error', function () {
    $user = User::factory()->create(['password' => Hash::make('a-strong-passphrase')]);

    $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertRedirect('/login')->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('lets a remembered login set the remember cookie', function () {
    $user = User::factory()->create(['password' => Hash::make('a-strong-passphrase')]);

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'a-strong-passphrase', 'remember' => '1']);

    $response->assertCookie(Auth::guard()->getRecallerName());
    $this->assertAuthenticatedAs($user);
});

it('refuses a suspended account that has valid credentials', function () {
    $user = User::factory()->suspended()->create(['password' => Hash::make('a-strong-passphrase')]);

    $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'a-strong-passphrase'])
        ->assertRedirect('/login')->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('allows an unverified user to sign in', function () {
    $user = User::factory()->unverified()->create(['password' => Hash::make('a-strong-passphrase')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'a-strong-passphrase'])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

it('logs out and revokes the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect(route('home'));
    $this->assertGuest();
});
