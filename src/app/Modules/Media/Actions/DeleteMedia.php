<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes an original and its derivatives from storage and the database.
 * Idempotent: missing objects or already-removed rows are ignored.
 */
class DeleteMedia
{
    public function handle(Media $media): void
    {
        $items = $media->variants()->get()->push($media);

        foreach ($items as $item) {
            Storage::disk($item->disk)->delete($item->path);
        }

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                $item->newQuery()->whereKey($item->getKey())->delete();
            }
        });
    }
}
