<?php

namespace App\Domain\Media\Events;

use Illuminate\Foundation\Events\Dispatchable;

class MediaFailed
{
    use Dispatchable;

    public function __construct(public int $mediaId, public string $reason) {}
}
