<?php

namespace App\Domain\Users\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Public seam: the search index and caches refresh when a profile changes (specs/05 event seam). */
class ProfileUpdated
{
    use Dispatchable;

    public function __construct(public int $userId) {}
}
