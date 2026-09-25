<?php

namespace App\Domain\Notifications\Listeners;

use App\Domain\Auth\Events\AccountAnonymized;
use App\Domain\Notifications\Services\NotificationReadModel;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class RemoveAccountNotifications implements ShouldQueueAfterCommit
{
    public string $queue = 'low';

    public function handle(AccountAnonymized $event): void
    {
        $inbox = app(NotificationReadModel::class);
        $inbox->owned($event->userId)->delete();
        $inbox->invalidate($event->userId);
    }
}
