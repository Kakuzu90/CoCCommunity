<?php

namespace App\Domain\Auth\Notifications;

use App\Support\Traits\QueuesSecurityMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OldEmailChangedNotification extends Notification implements ShouldQueue
{
    use QueuesSecurityMail;

    public function __construct()
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Clash Commons email changed')
            ->line('The email address on your Clash Commons account was changed. If this was not you, reset your password immediately.');
    }
}
