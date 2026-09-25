<?php

namespace App\Domain\Notifications\Listeners;

use App\Domain\Notifications\Events\NoticeRequested;
use App\Domain\Notifications\Services\Notifier;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class SendNotice implements ShouldQueueAfterCommit
{
    public string $queue = 'high';

    public int $tries = 3;

    public function handle(NoticeRequested $event): void
    {
        app(Notifier::class)->send($event);
    }
}
