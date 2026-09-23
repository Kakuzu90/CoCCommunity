<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Gate;

test('media starts pending and stores object metadata only', function (): void {
    $user = User::factory()->create();
    $media = new Media(['disk' => 'r2', 'path' => 'uploads/image.jpg', 'mime' => 'image/jpeg', 'size' => 1024]);
    $media->uploader_id = $user->id;
    $media->save();

    expect($media->fresh()->status)->toBe(MediaStatus::Pending);
    expect(Gate::forUser($user)->allows('view', $media))->toBeTrue();
    expect(Gate::forUser($user)->allows('delete', $media))->toBeTrue();
    expect($media->isFillable('status'))->toBeFalse();
    expect($media->isFillable('uploader_id'))->toBeFalse();
    expect(Gate::allows('view', $media))->toBeFalse();
});

test('another user cannot access private media even with an elevated role', function (Role $role): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $other->assignRole($role);
    $media = new Media(['path' => 'uploads/private.jpg', 'mime' => 'image/jpeg', 'size' => 100]);
    $media->uploader_id = $owner->id;
    $media->save();

    expect(Gate::forUser($other)->allows('view', $media))->toBeFalse();
    expect(Gate::forUser($other)->allows('delete', $media))->toBeFalse();
})->with(Role::cases());
