<?php

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Models\User;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Models\Media;
use App\Domain\Users\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * specs/11: profile editing is a write surface taking user input. Mass-assignment, stored-XSS and
 * IDOR are the realistic threats.
 */

it('ignores privileged and foreign columns posted to the profile update', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('settings.profile.update'), [
        'display_name' => 'Legit',
        'role' => UserRole::Admin->value,
        'status' => UserStatus::Banned->value,
        'user_id' => 999999,
        'avatar_media_id' => 4242,
        'id' => 5,
    ])->assertRedirect();

    $user->refresh();
    expect($user->role)->toBe(UserRole::User)
        ->and($user->status)->toBe(UserStatus::Active);

    $profile = Profile::where('user_id', $user->id)->firstOrFail();
    expect($profile->user_id)->toBe($user->id)
        ->and($profile->avatar_media_id)->toBeNull()
        ->and($profile->display_name)->toBe('Legit');
});

it('stores the bio raw and escapes it on render (no stored XSS)', function () {
    $user = User::factory()->create();
    $payload = '<script>alert(1)</script>';

    $this->actingAs($user)->put(route('settings.profile.update'), ['bio' => $payload])->assertRedirect();

    expect(Profile::where('user_id', $user->id)->firstOrFail()->bio)->toBe($payload);

    $this->actingAs($user)->get(route('settings.profile.edit'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('does not let a user attach another user\'s media as their avatar', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $media = Media::factory()->forUser($owner)->ready()->create([
        'collection' => MediaCollection::Avatar->value,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($attacker)->from(route('settings.profile.edit'))
        ->post(route('settings.profile.avatar.store'), ['media_ulid' => $media->ulid])
        ->assertSessionHasErrors('media');

    $media->refresh();
    expect($media->attachable_id)->toBeNull();
});
