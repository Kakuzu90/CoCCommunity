<?php

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('sends a branded reset link for a known email', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('gives the same generic response for an unknown email and sends nothing', function () {
    Notification::fake();

    $this->post('/forgot-password', ['email' => 'nobody@gmail.com'])->assertSessionHas('status');

    Notification::assertNothingSent();
});

it('resets the password with a valid token and revokes other sessions', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password-value')]);
    // A live session for this user that the reset must kill.
    DB::table('sessions')->insert([
        'id' => 'other-session', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
        'user_agent' => 'x', 'payload' => 'x', 'last_activity' => now()->timestamp,
    ]);

    $token = Password::createToken($user);
    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'a-brand-new-passphrase',
        'password_confirmation' => 'a-brand-new-passphrase',
    ])->assertRedirect(route('login'));

    expect(Hash::check('a-brand-new-passphrase', $user->refresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'other-session')->exists())->toBeFalse();
});

it('rejects a reused (single-use) reset token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->post('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'a-brand-new-passphrase', 'password_confirmation' => 'a-brand-new-passphrase'])
        ->assertRedirect(route('login'));

    $this->from('/reset-password/'.$token)->post('/reset-password', [
        'token' => $token, 'email' => $user->email,
        'password' => 'another-new-passphrase', 'password_confirmation' => 'another-new-passphrase',
    ])->assertSessionHasErrors('email');
});
