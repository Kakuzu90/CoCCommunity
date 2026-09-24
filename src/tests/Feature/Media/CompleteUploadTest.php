<?php

use App\Domain\Auth\Models\User;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Jobs\ProcessMediaJob;
use App\Domain\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

it('marks the media uploaded and dispatches processing once', function () {
    $user = User::factory()->create();
    $media = Media::factory()->forUser($user)->create();

    $this->actingAs($user)->postJson("/uploads/{$media->ulid}/complete")
        ->assertOk()
        ->assertJson(['status' => 'uploaded']);

    expect($media->refresh()->status)->toBe(MediaStatus::Uploaded);
    Queue::assertPushed(ProcessMediaJob::class, 1);
});

it('is idempotent when called twice', function () {
    $user = User::factory()->create();
    $media = Media::factory()->forUser($user)->create();

    $this->actingAs($user)->postJson("/uploads/{$media->ulid}/complete")->assertOk();
    $this->actingAs($user)->postJson("/uploads/{$media->ulid}/complete")->assertOk();

    Queue::assertPushed(ProcessMediaJob::class, 1);
});

it('does not dispatch when the media is already processing', function () {
    $user = User::factory()->create();
    $media = Media::factory()->forUser($user)->create(['status' => MediaStatus::Processing->value]);

    $this->actingAs($user)->postJson("/uploads/{$media->ulid}/complete")->assertOk();

    Queue::assertNothingPushed();
});

it('cannot complete another user\'s upload', function () {
    $media = Media::factory()->forUser(User::factory()->create())->create();

    $this->actingAs(User::factory()->create())->postJson("/uploads/{$media->ulid}/complete")
        ->assertNotFound();

    Queue::assertNothingPushed();
});
