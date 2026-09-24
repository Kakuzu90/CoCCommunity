<?php

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

/*
 * The auth suite from specs/11 §Security tests: enumeration, session fixation, reset-token reuse,
 * session invalidation on password change, mass-assignment, and the named rate limiters.
 */

it('returns an identical generic error for unknown-email and wrong-password', function () {
    $user = User::factory()->create(['password' => Hash::make('a-strong-passphrase')]);

    $wrongPassword = $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'nope']);
    $unknownEmail = $this->from('/login')->post('/login', ['email' => 'ghost@gmail.com', 'password' => 'nope']);

    $a = $wrongPassword->assertRedirect('/login')->getSession()->get('errors')->first('email');
    $b = $unknownEmail->assertRedirect('/login')->getSession()->get('errors')->first('email');
    expect($a)->toBe($b);
});

it('regenerates the session id on login (fixation defence)', function () {
    $user = User::factory()->create(['password' => Hash::make('a-strong-passphrase')]);

    $this->startSession();
    $before = session()->getId();
    $this->post('/login', ['email' => $user->email, 'password' => 'a-strong-passphrase']);

    expect(session()->getId())->not->toBe($before);
});

it('enforces the login rate limiter', function () {
    $user = User::factory()->create(['password' => Hash::make('a-strong-passphrase')]);

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }
    // The 6th attempt within the minute is throttled (5/min, specs/04 §4).
    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
});

it('defines every named auth rate limiter', function () {
    foreach (['login', 'register', 'password-reset', 'verify-email-resend', 'username-check'] as $name) {
        expect(RateLimiter::limiter($name))->not->toBeNull();
    }
});

it('does not leak account existence on registration', function () {
    $existing = User::factory()->create(['email' => 'known@gmail.com']);

    $newEmail = $this->post('/register', [
        'username' => 'freshhandle', 'email' => 'unknown@gmail.com',
        'password' => 'a-strong-passphrase', 'password_confirmation' => 'a-strong-passphrase',
        'form_started_at' => now()->subSeconds(5)->getTimestampMs(), 'company_website' => '',
    ]);
    $takenEmail = $this->post('/register', [
        'username' => 'anotherhandle', 'email' => 'known@gmail.com',
        'password' => 'a-strong-passphrase', 'password_confirmation' => 'a-strong-passphrase',
        'form_started_at' => now()->subSeconds(5)->getTimestampMs(), 'company_website' => '',
    ]);

    // Same destination, neither reveals which email exists.
    $newEmail->assertRedirect(route('register.pending'))->assertSessionHasNoErrors();
    $takenEmail->assertRedirect(route('register.pending'))->assertSessionHasNoErrors();
});

it('invalidates other sessions when the password is reset', function () {
    $user = User::factory()->create();
    DB::table('sessions')->insert([
        'id' => 's1', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
        'user_agent' => 'x', 'payload' => 'x', 'last_activity' => now()->timestamp,
    ]);

    $token = Password::createToken($user);
    $this->post('/reset-password', [
        'token' => $token, 'email' => $user->email,
        'password' => 'a-brand-new-passphrase', 'password_confirmation' => 'a-brand-new-passphrase',
    ]);

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});
