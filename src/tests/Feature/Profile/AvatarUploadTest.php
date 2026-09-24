<?php

use App\Domain\Auth\Models\User;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Models\Media;
use App\Domain\Media\Models\MediaVariant;
use App\Domain\Users\Models\Profile;
use App\Domain\Users\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A processed, ready avatar draft owned by $user (unattached, still carrying its expiry). */
function readyAvatar(User $user): Media
{
    $media = Media::factory()->forUser($user)->ready()->create([
        'collection' => MediaCollection::Avatar->value,
        'expires_at' => now()->addDay(),
    ]);
    MediaVariant::create([
        'media_id' => $media->id,
        'variant' => 'card',
        'path' => "public/avatar/{$media->ulid}/card.webp",
        'width' => 128, 'height' => 128, 'size_bytes' => 900, 'mime_type' => 'image/webp',
    ]);

    return $media;
}

it('attaches a ready avatar and points the profile at it', function () {
    $user = User::factory()->create();
    $media = readyAvatar($user);

    $this->actingAs($user)->post(route('settings.profile.avatar.store'), ['media_ulid' => $media->ulid])
        ->assertRedirect(route('settings.profile.edit'))
        ->assertSessionHas('status', 'avatar-updated');

    $profile = Profile::where('user_id', $user->id)->firstOrFail();
    expect($profile->avatar_media_id)->toBe($media->id);

    $media->refresh();
    expect($media->attachable_type)->toBe($profile->getMorphClass())
        ->and($media->attachable_id)->toBe($profile->id)
        ->and($media->expires_at)->toBeNull();
});

it('releases the previous avatar when replaced', function () {
    $user = User::factory()->create();
    $first = readyAvatar($user);
    $second = readyAvatar($user);
    $service = app(ProfileService::class);

    $service->setAvatar($user, $first->ulid);
    $service->setAvatar($user, $second->ulid);

    $first->refresh();
    expect($first->attachable_id)->toBeNull()
        ->and($first->expires_at)->not->toBeNull();

    expect(Profile::where('user_id', $user->id)->firstOrFail()->avatar_media_id)->toBe($second->id);
});

it('removes an avatar and releases its media', function () {
    $user = User::factory()->create();
    $media = readyAvatar($user);
    $service = app(ProfileService::class);
    $service->setAvatar($user, $media->ulid);

    $this->actingAs($user)->delete(route('settings.profile.avatar.destroy'))
        ->assertRedirect()->assertSessionHas('status', 'avatar-removed');

    expect(Profile::where('user_id', $user->id)->firstOrFail()->avatar_media_id)->toBeNull();
    $media->refresh();
    expect($media->attachable_id)->toBeNull()->and($media->expires_at)->not->toBeNull();
});

it('refuses to attach media the user does not own', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $media = readyAvatar($owner);

    $this->actingAs($attacker)->from(route('settings.profile.edit'))
        ->post(route('settings.profile.avatar.store'), ['media_ulid' => $media->ulid])
        ->assertSessionHasErrors('media');

    expect(Profile::where('user_id', $attacker->id)->first()?->avatar_media_id)->toBeNull();
});

it('refuses media from the wrong collection', function () {
    $user = User::factory()->create();
    $media = Media::factory()->forUser($user)->ready()->create([
        'collection' => MediaCollection::BaseScreenshot->value,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)->from(route('settings.profile.edit'))
        ->post(route('settings.profile.avatar.store'), ['media_ulid' => $media->ulid])
        ->assertSessionHasErrors('media');
});

it('refuses media that is not yet processed', function () {
    $user = User::factory()->create();
    $media = Media::factory()->forUser($user)->create([
        'collection' => MediaCollection::Avatar->value,
        'status' => MediaStatus::Pending->value,
    ]);

    $this->actingAs($user)->from(route('settings.profile.edit'))
        ->post(route('settings.profile.avatar.store'), ['media_ulid' => $media->ulid])
        ->assertSessionHasErrors('media');
});
