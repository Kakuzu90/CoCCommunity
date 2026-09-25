<?php

namespace App\Domain\Auth\Notifications;

use App\Support\Traits\QueuesSecurityMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when someone tries to register with an email that already has an account (specs/11): the
 * account-existence signal is delivered by email, never by the registration form. It doubles as a
 * heads-up to the real owner that someone attempted to sign up with their address.
 */
final class ExistingAccountNotification extends Notification implements ShouldQueue
{
    use QueuesSecurityMail;

    public function __construct()
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You already have a Clash Commons account')
            ->line('Someone just tried to register with this email address, which already has an account.')
            ->line('If that was you, simply sign in. If you forgot your password, reset it below.')
            ->action('Reset password', url(route('password.request')))
            ->line('If this was not you, no action is needed — your account is unchanged.');
    }
}
