<?php

namespace App\Domain\Auth\Notifications;

use App\Support\Traits\QueuesSecurityMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewEmailChangedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)->subject('Your Clash Commons email changed')
            ->line('This address is now used for your Clash Commons account. A separate verification link has been sent to confirm it.')
            ->line('If you did not make this change, reset your password from the sign-in page.');
    }
}
