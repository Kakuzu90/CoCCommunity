<?php

namespace App\Domain\Auth\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Branded email-verification message (specs/16). Uses the framework's signed-URL machinery; only
 * the copy is ours. Full templated notifications land with Notifications v1.
 */
final class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your email · Clash Commons')
            ->greeting('Welcome to Clash Commons')
            ->line('Confirm this email address to activate your account and start sharing bases.')
            ->action('Verify email', $url)
            ->line('The link expires in 60 minutes. If you did not create an account, ignore this email.');
    }
}
