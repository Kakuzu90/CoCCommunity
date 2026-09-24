<?php

namespace App\Domain\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Branded password-reset message (specs/16). Token single-use, 60-minute expiry (specs/11). */
final class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()], false));

        return (new MailMessage)
            ->subject('Reset your password · Clash Commons')
            ->line('We received a request to reset your Clash Commons password.')
            ->action('Reset password', $url)
            ->line('This link expires in 60 minutes and can be used once.')
            ->line('If you did not request this, no action is needed — your password stays unchanged.');
    }
}
