<?php

namespace App\Domain\Notifications\Events;

use App\Domain\Notifications\Enums\NoticeKind;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Str;

final readonly class NoticeRequested implements ShouldDispatchAfterCommit
{
    public string $id;

    public function __construct(public int $userId, public NoticeKind $kind)
    {
        $this->id = (string) Str::uuid();
    }
}
