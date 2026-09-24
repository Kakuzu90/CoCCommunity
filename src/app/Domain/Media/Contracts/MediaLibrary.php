<?php

namespace App\Domain\Media\Contracts;

use App\Domain\Media\Data\MediaImage;
use App\Domain\Media\Enums\MediaCollection;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * The seam consuming modules use to attach uploaded media to their own records and to render it,
 * without reaching into the Media model (specs/10 §3, specs/19 §2). Attachment is what makes an
 * upload permanent — until then the orphan sweeper owns it.
 */
interface MediaLibrary
{
    /**
     * Attach a user's uploaded, processed media to a parent record: assert ownership and collection,
     * assert the media is usable (ready/processing), point it at the parent and clear its expiry.
     * Returns the media id. Throws when the media is missing, foreign, the wrong collection, or in a
     * state that cannot be attached.
     */
    public function attach(Authenticatable $user, string $ulid, MediaCollection $collection, Model $attachable): int;

    /** Render-ready view of a processed image, or null when the id is null/missing/not ready. */
    public function resolve(?int $mediaId): ?MediaImage;

    /**
     * Detach a media record and hand it back to the orphan sweeper (a replaced avatar, a removed
     * screenshot). A no-op for a null/missing id. The object is deleted by the sweeper, not here.
     */
    public function release(?int $mediaId): void;
}
