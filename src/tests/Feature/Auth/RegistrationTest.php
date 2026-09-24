<?php

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Notifications\ExistingAccountNotification;
use App\Domain\Auth\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/** @return array<string, mixed> */
function validRegistration(array $overrides = []): array
{
    return array_merge([
        'username' => 'clashfan99',
        'email' => 'newplayer@gmail.com',
        'password' => 'a-strong-passphrase',
        'password_confirmation' => 'a-strong-passphrase',
        'form_started_at' => now()->subSeconds(5)->getTimestampMs(),
        'company_website' => '',
    ], $overrides);
}

it('registers a new user, keeps them unverified, and sends a verification email', function () {
    Notification::fake();

    $this->post('/register', validRegistration())->assertRedirect(route('register.pending'));

    $user = User::where('email', 'newplayer@gmail.com')->firstOrFail();
    expect($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->ulid)->toHaveLength(26);

    Notification::assertSentTo($user, VerifyEmailNotification::class);
    $this->assertGuest();
});

it('never mass-assigns role or status from the form', function () {
    $this->post('/register', validRegistration([
        'role' => 'admin',
        'status' => 'banned',
        'verified_accounts_count' => 99,
    ]))->assertRedirect(route('register.pending'));

    $user = User::where('email', 'newplayer@gmail.com')->firstOrFail();
    expect($user->role)->toBe(UserRole::User)
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->verified_accounts_count)->toBe(0);
});

it('rejects reserved usernames', function () {
    $this->from('/register')->post('/register', validRegistration(['username' => 'Admin']))
        ->assertRedirect('/register')->assertSessionHasErrors('username');
    expect(User::count())->toBe(0);
});

it('rejects disposable email domains', function () {
    $this->post('/register', validRegistration(['email' => 'burner@mailinator.com']))
        ->assertSessionHasErrors('email');
    expect(User::count())->toBe(0);
});

it('rejects passwords shorter than ten characters', function () {
    $this->post('/register', validRegistration(['password' => 'short', 'password_confirmation' => 'short']))
        ->assertSessionHasErrors('password');
});

it('rejects a filled honeypot', function () {
    $this->post('/register', validRegistration(['company_website' => 'http://spam.example']))
        ->assertSessionHasErrors('company_website');
    expect(User::count())->toBe(0);
});

it('rejects a form submitted implausibly fast', function () {
    $this->post('/register', validRegistration(['form_started_at' => now()->getTimestampMs()]))
        ->assertSessionHasErrors('form_started_at');
});

it('does not reveal that an email already exists', function () {
    Notification::fake();
    $existing = User::factory()->create(['email' => 'taken@gmail.com']);

    $this->post('/register', validRegistration(['username' => 'brandnew', 'email' => 'taken@gmail.com']))
        ->assertRedirect(route('register.pending'))
        ->assertSessionHasNoErrors();

    // No second account, and the real owner — not the form — gets the signal.
    expect(User::where('email', 'taken@gmail.com')->count())->toBe(1);
    Notification::assertSentTo($existing, ExistingAccountNotification::class);
});
