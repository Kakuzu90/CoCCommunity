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

function orphan(FakeMediaStorage $storage, array $attributes): Media
{
    $media = Media::factory()->forUser(User::factory()->create())->create($attributes);
    $storage->put($media->path, 'bytes', 'public', 'image/png');

    return $media;
}

it('deletes expired unattached media and its objects', function () {
    $media = orphan($this->storage, [
        'status' => MediaStatus::Uploaded->value,
        'expires_at' => now()->subHour(),
    ]);
    $key = $media->path;

    $this->artisan('media:sweep-orphans')->assertSuccessful();

    expect(Media::find($media->id))->toBeNull()
        ->and($this->storage->exists($key))->toBeFalse();
});

it('sweeps a ready draft that was never attached', function () {
    $media = orphan($this->storage, [
        'status' => MediaStatus::Ready->value,
        'expires_at' => now()->subHour(),
    ]);

    $this->artisan('media:sweep-orphans')->assertSuccessful();

    expect(Media::find($media->id))->toBeNull();
});

it('never sweeps attached media', function () {
    $media = orphan($this->storage, [
        'status' => MediaStatus::Ready->value,
        'attachable_type' => 'base',
        'attachable_id' => 1,
        'expires_at' => now()->subHour(),
    ]);

    $this->artisan('media:sweep-orphans');

    expect(Media::find($media->id))->not->toBeNull();
});

it('never sweeps quarantined media', function () {
    $media = orphan($this->storage, [
        'status' => MediaStatus::Quarantined->value,
        'expires_at' => now()->subHour(),
    ]);

    $this->artisan('media:sweep-orphans');

    expect(Media::find($media->id))->not->toBeNull();
});

it('leaves media that has not expired yet', function () {
    $media = orphan($this->storage, [
        'status' => MediaStatus::Uploaded->value,
        'expires_at' => now()->addHour(),
    ]);

    $this->artisan('media:sweep-orphans');

    expect(Media::find($media->id))->not->toBeNull();
});

it('deletes nothing on a dry run', function () {
    $media = orphan($this->storage, [
        'status' => MediaStatus::Uploaded->value,
        'expires_at' => now()->subHour(),
    ]);

    $this->artisan('media:sweep-orphans', ['--dry-run' => true])
        ->expectsOutputToContain('would delete 1')
        ->assertSuccessful();

    expect(Media::find($media->id))->not->toBeNull()
        ->and($this->storage->exists($media->path))->toBeTrue();
});
