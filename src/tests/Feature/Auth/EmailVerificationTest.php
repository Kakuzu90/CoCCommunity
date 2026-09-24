<?php

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

function verificationUrl(User $user): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => sha1($user->getEmailForVerification()),
    ]);
}

it('verifies email from a signed link even when signed out', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    $this->get(verificationUrl($user))->assertRedirect(route('login'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
    $this->assertGuest();
});

it('rejects a link whose hash does not match the email', function () {
    $user = User::factory()->unverified()->create();

    $bad = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => sha1('someone-else@example.com'),
    ]);

    $this->get($bad)->assertForbidden();
    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects a tampered (unsigned) link', function () {
    $user = User::factory()->unverified()->create();

    $this->get(route('verification.verify', ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]))
        ->assertForbidden();
});

it('resends the verification email to an authenticated unverified user', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post('/email/verification-notification')->assertRedirect();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});
