<?php

namespace App\Domain\Media\Services;

use App\Domain\Media\Contracts\MediaLibrary;
use App\Domain\Media\Data\MediaImage;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Models\Media;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Concrete MediaLibrary (specs/10 §3). Attachment is scoped to the owning user and collection so a
 * client cannot attach someone else's upload or cross collections; the query itself 404s a foreign
 * id before any state changes.
 */
class MediaLibraryService implements MediaLibrary
{
    /** Media in these states may be attached — `ready`, or still `processing` (specs/10 §3). */
    private const ATTACHABLE = [MediaStatus::Ready, MediaStatus::Processing];

    public function __construct(private readonly MediaUrlResolver $urls) {}

    public function attach(Authenticatable $user, string $ulid, MediaCollection $collection, Model $attachable): int
    {
        $media = Media::query()
            ->where('ulid', $ulid)
            ->where('user_id', $user->getAuthIdentifier())
            ->where('collection', $collection->value)
            ->lockForUpdate()
            ->first();

        if ($media === null || ! in_array($media->status, self::ATTACHABLE, true)) {
            throw ValidationException::withMessages([
                'media' => 'That upload is not available to attach.',
            ]);
        }
        if ($media->attachable_id !== null) {
            throw ValidationException::withMessages(['media' => 'That upload has already been attached.']);
        }

        $media->attachable_type = $attachable->getMorphClass();
        $media->attachable_id = $attachable->getKey();
        $media->expires_at = null; // Attachment makes it permanent (specs/10 §9).
        $media->save();

        return $media->id;
    }

    public function resolve(?int $mediaId): ?MediaImage
    {
        if ($mediaId === null) {
            return null;
        }

        $media = Media::query()
            ->with('variants')
            ->where('id', $mediaId)
            ->where('status', MediaStatus::Ready->value)
            ->first();

        if ($media === null) {
            return null;
        }

        return $this->toImage($media);
    }

    /** @return list<MediaImage> */
    public function imagesFor(Model $attachable, MediaCollection $collection): array
    {
        return array_values(Media::query()->with('variants')
            ->where('attachable_type', $attachable->getMorphClass())
            ->where('attachable_id', $attachable->getKey())
            ->where('collection', $collection->value)
            ->where('status', MediaStatus::Ready->value)
            ->orderBy('position')->orderBy('id')->get()
            ->map(fn (Media $media): MediaImage => $this->toImage($media))->all());
    }

    public function releaseAttached(string $ulid, MediaCollection $collection, Model $attachable): bool
    {
        $media = Media::query()->where('ulid', $ulid)
            ->where('collection', $collection->value)
            ->where('attachable_type', $attachable->getMorphClass())
            ->where('attachable_id', $attachable->getKey())->lockForUpdate()->first();
        if ($media === null) {
            return false;
        }
        $this->release($media->id);

        return true;
    }

    private function toImage(Media $media): MediaImage
    {
        $variants = [];
        foreach ($media->variants as $variant) {
            $variants[$variant->variant] = $this->urls->public($variant->path);
        }

        return new MediaImage($media->ulid, $media->collection->value, $media->width, $media->height, $variants);
    }

    public function release(?int $mediaId): void
    {
        if ($mediaId === null) {
            return;
        }

        $media = Media::find($mediaId);
        if ($media === null) {
            return;
        }

        // Detach and hand back to the sweeper: unattached + an expiry it will reap (specs/10 §9).
        $media->attachable_type = null;
        $media->attachable_id = null;
        $media->expires_at = now()->addHours((int) config('media.intent.expiry_hours'));
        $media->save();
    }
}
