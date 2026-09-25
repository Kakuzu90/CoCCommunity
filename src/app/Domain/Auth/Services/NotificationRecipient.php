<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Models\User;
use Illuminate\Notifications\Notification;

final class NotificationRecipient
{
    public function send(int $userId, Notification $notification): void
    {
        User::query()->find($userId)?->notify($notification);
    }

    public function morphType(): string
    {
        return (new User)->getMorphClass();
    }
}
