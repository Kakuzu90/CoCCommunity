<?php

use App\Domain\Auth\Models\User;
use App\Domain\Media\Contracts\MediaStorage;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Models\Media;
use App\Domain\Media\Storage\FakeMediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->storage = new FakeMediaStorage;
    $this->app->instance(MediaStorage::class, $this->storage);
});

it('issues a presigned ticket and creates a pending media row', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/uploads/intent', [
        'collection' => 'base_screenshot',
        'filename' => 'my base.png',
        'size' => 1_000_000,
        'mime' => 'image/png',
    ])->assertCreated();

    $ulid = $response->json('media_ulid');
    expect($response->json('upload_url'))->toContain($ulid)
        ->and($response->json('expires_in'))->toBe(config('media.intent.presign_ttl'))
        ->and($response->json('max_size'))->toBe(config('media.collections.base_screenshot.max_size'));

    $media = Media::where('ulid', $ulid)->sole();
    expect($media->status)->toBe(MediaStatus::Pending)
        ->and($media->user_id)->toBe($user->id)
        ->and($media->path)->toStartWith('quarantine/')
        ->and($media->expires_at)->not->toBeNull();
});

it('requires authentication', function () {
    $this->postJson('/uploads/intent', [
        'collection' => 'avatar', 'filename' => 'a.png', 'size' => 100, 'mime' => 'image/png',
    ])->assertUnauthorized();
});

it('rejects an unknown or not-yet-enabled collection', function () {
    $this->actingAs(User::factory()->create())->postJson('/uploads/intent', [
        'collection' => 'base_video', 'filename' => 'clip.mp4', 'size' => 100, 'mime' => 'video/mp4',
    ])->assertJsonValidationErrorFor('collection');
});

it('rejects a file larger than the collection allows', function () {
    $this->actingAs(User::factory()->create())->postJson('/uploads/intent', [
        'collection' => 'avatar',
        'filename' => 'big.png',
        'size' => config('media.collections.avatar.max_size') + 1,
        'mime' => 'image/png',
    ])->assertJsonValidationErrorFor('size');
});

it('rejects a declared mime outside the collection allowlist', function () {
    $this->actingAs(User::factory()->create())->postJson('/uploads/intent', [
        'collection' => 'avatar', 'filename' => 'a.gif', 'size' => 100, 'mime' => 'image/gif',
    ])->assertJsonValidationErrorFor('mime');
});

it('blocks uploads once the per-user storage cap is reached', function () {
    $user = User::factory()->create();
    Media::factory()->forUser($user)->create(['size_bytes' => config('media.user_soft_cap')]);

    $this->actingAs($user)->postJson('/uploads/intent', [
        'collection' => 'avatar', 'filename' => 'a.png', 'size' => 500, 'mime' => 'image/png',
    ])->assertJsonValidationErrorFor('size');
});

it('rate limits intents to 30 per hour', function () {
    $user = User::factory()->create();
    $payload = ['collection' => 'avatar', 'filename' => 'a.png', 'size' => 100, 'mime' => 'image/png'];

    for ($i = 0; $i < 30; $i++) {
        $this->actingAs($user)->postJson('/uploads/intent', $payload)->assertCreated();
    }

    $this->actingAs($user)->postJson('/uploads/intent', $payload)->assertStatus(429);
});
