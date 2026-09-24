<?php

use App\Domain\Auth\Models\User;
use App\Domain\Media\Contracts\MediaLibrary;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Models\Media;
use App\Domain\Media\Models\MediaVariant;
use App\Domain\Users\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves a ready image to variant URLs and null for a missing id', function () {
    $library = app(MediaLibrary::class);
    $media = Media::factory()->ready()->create([
        'collection' => MediaCollection::Avatar->value,
        'expires_at' => now()->addDay(),
        'width' => 512, 'height' => 512,
    ]);
    MediaVariant::create([
        'media_id' => $media->id, 'variant' => 'card',
        'path' => "public/avatar/{$media->ulid}/card.webp",
        'width' => 128, 'height' => 128, 'size_bytes' => 900, 'mime_type' => 'image/webp',
    ]);

    $image = $library->resolve($media->id);
    expect($image)->not->toBeNull()
        ->and($image->url('card'))->toContain("public/avatar/{$media->ulid}/card.webp")
        ->and($image->width)->toBe(512);

    expect($library->resolve(null))->toBeNull()
        ->and($library->resolve(999999))->toBeNull();
});

it('does not resolve media that is not ready', function () {
    $media = Media::factory()->uploaded()->create(['collection' => MediaCollection::Avatar->value]);

    expect(app(MediaLibrary::class)->resolve($media->id))->toBeNull();
});

it('re-expires media on release so the sweeper reclaims it', function () {
    $user = User::factory()->create();
    $profile = app(ProfileService::class)->ensure($user->id);
    $media = Media::factory()->forUser($user)->ready()->create([
        'collection' => MediaCollection::Avatar->value,
        'expires_at' => now()->addDay(),
    ]);

    $library = app(MediaLibrary::class);
    $library->attach($user, $media->ulid, MediaCollection::Avatar, $profile);
    expect($media->fresh()->expires_at)->toBeNull();

    $library->release($media->id);
    $media->refresh();
    expect($media->attachable_id)->toBeNull()->and($media->expires_at)->not->toBeNull();
});
