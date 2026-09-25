<?php

namespace App\Domain\Notifications\Notifications;

use App\Domain\Notifications\Channels\InboxChannel;
use App\Domain\Notifications\Enums\NoticeKind;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly NoticeKind $kind, string $id)
    {
        $this->id = $id;
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->kind->sendsEmail() ? [InboxChannel::class, 'mail'] : [InboxChannel::class];
    }

    /** @return array<string, string> */
    public function viaQueues(): array
    {
        return [InboxChannel::class => 'high', 'mail' => 'low'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->kind->title().' · Clash Commons')
            ->line($this->kind->message());

        if (in_array($this->kind, [NoticeKind::PasswordChanged, NoticeKind::NewSignIn], true)) {
            $mail->action('Review sessions', route('settings.sessions.index'))
                ->line('If you cannot sign in, request a password reset from the sign-in page.');
        }

        return $mail;
    }
}
