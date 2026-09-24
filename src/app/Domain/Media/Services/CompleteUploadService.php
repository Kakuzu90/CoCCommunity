<?php

namespace App\Domain\Media\Services;

use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Jobs\ProcessMediaJob;
use App\Domain\Media\Models\Media;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Step 2 (specs/10 §3): the client says the PUT is done. Mark the row `uploaded` and queue
 * processing exactly once. Idempotent — repeated calls while processing/ready are a no-op.
 */
class CompleteUploadService
{
    public function complete(Authenticatable $user, string $ulid): Media
    {
        $media = Media::query()
            ->where('ulid', $ulid)
            ->where('user_id', $user->getAuthIdentifier())
            ->firstOrFail();

        // Only a pending row transitions and dispatches; anything further along is already handled.
        if ($media->status === MediaStatus::Pending) {
            $media->status = MediaStatus::Uploaded;
            $media->save();

            ProcessMediaJob::dispatch($media->id);
        }

        return $media;
    }
}
