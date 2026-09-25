<?php

namespace App\Domain\Bases\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class BasePublished
{
    use Dispatchable;

    public function __construct(public int $baseId) {}
}
