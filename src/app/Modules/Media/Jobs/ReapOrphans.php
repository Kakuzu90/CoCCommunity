<?php

declare(strict_types=1);

namespace App\Modules\Media\Jobs;

use App\Modules\Media\Actions\DeleteMedia;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Removes uploads that were reserved/started but never finalised, so abandoned
 * objects don't accumulate in storage. Runs on a schedule.
 */
class ReapOrphans implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(DeleteMedia $delete): void
    {
        $cutoff = now()->subHours((int) config('media.orphan_ttl_hours'));

        Media::query()
            ->whereNull('parent_id')
            ->where('status', MediaStatus::Pending->value)
            ->where('created_at', '<', $cutoff)
            ->get()
            ->each(fn (Media $media) => $delete->handle($media));
    }
}
