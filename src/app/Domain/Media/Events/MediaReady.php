<?php

namespace App\Domain\Media\Events;

use Illuminate\Foundation\Events\Dispatchable;

// Public seam other modules subscribe to (e.g. publish a base when its media is ready).
class MediaReady
{
    use Dispatchable;

    public function __construct(public int $mediaId) {}
}
