<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Auth\Services\NotificationRecipient;
use App\Domain\Notifications\Events\NoticeRequested;
use App\Domain\Notifications\Notifications\AccountNotice;

final class Notifier
{
    public function __construct(private readonly NotificationRecipient $recipients) {}

    public function send(NoticeRequested $event): void
    {
        $this->recipients->send($event->userId, new AccountNotice($event->kind, $event->id));
    }
}
