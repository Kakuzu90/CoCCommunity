<?php

namespace App\Domain\Media\Services;

use App\Domain\Media\Contracts\MediaStorage;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Models\Media;

/**
 * Deletes unattached media past its expiry and the objects behind it (specs/10 §9): abandoned
 * intents, uploads whose `complete` never came, and ready drafts never published. Quarantined
 * media is retained separately and never swept here. Returns the number of media removed.
 */
class OrphanMediaSweeper
{
    public function __construct(private readonly MediaStorage $storage) {}

    public function sweep(bool $dryRun = false): int
    {
        $deleted = 0;

        Media::query()
            ->with('variants')
            ->whereNull('attachable_id')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereIn('status', [
                MediaStatus::Pending->value,
                MediaStatus::Uploaded->value,
                MediaStatus::Ready->value,
                MediaStatus::Failed->value,
            ])
            ->chunkById(200, function ($orphans) use ($dryRun, &$deleted) {
                foreach ($orphans as $media) {
                    if (! $dryRun) {
                        $keys = array_merge([$media->path], $media->variants->pluck('path')->all());
                        $this->storage->delete(...$keys);
                        $media->forceDelete();
                    }

                    $deleted++;
                }
            });

        return $deleted;
    }
}
