<?php

namespace Database\Factories;

use App\Domain\Auth\Models\User;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Enums\MediaVisibility;
use App\Domain\Media\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Media> */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $ulid = (string) Str::ulid();

        return [
            'ulid' => $ulid,
            'user_id' => User::factory(),
            'collection' => MediaCollection::Avatar->value,
            'kind' => MediaKind::Image->value,
            'disk' => 'r2',
            'path' => "quarantine/2026/09/{$ulid}/original.png",
            'original_filename' => 'screenshot.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 1024,
            'status' => MediaStatus::Pending->value,
            'visibility' => MediaVisibility::Public->value,
            'position' => 0,
            'expires_at' => now()->addHours(24),
        ];
    }

    public function forUser(User|int $user): static
    {
        return $this->state(fn () => ['user_id' => $user instanceof User ? $user->id : $user]);
    }

    public function uploaded(): static
    {
        return $this->state(fn () => ['status' => MediaStatus::Uploaded->value]);
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'status' => MediaStatus::Ready->value,
            'processed_at' => now(),
            'mime_type' => 'image/webp',
            'extension' => 'webp',
            'expires_at' => null,
        ]);
    }
}
