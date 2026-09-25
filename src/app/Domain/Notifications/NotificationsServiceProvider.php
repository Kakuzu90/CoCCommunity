<?php

namespace App\Domain\Notifications;

use App\Domain\Auth\Events\AccountAnonymized;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Events\NoticeRequested;
use App\Domain\Notifications\Listeners\RemoveAccountNotifications;
use App\Domain\Notifications\Listeners\SendNotice;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class NotificationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(NoticeRequested::class, SendNotice::class);
        Event::listen(AccountAnonymized::class, RemoveAccountNotifications::class);
        Event::listen(Verified::class, function (Verified $event): void {
            if ($event->user instanceof Authenticatable) {
                event(new NoticeRequested((int) $event->user->getAuthIdentifier(), NoticeKind::EmailVerified));
            }
        });
        Event::listen(PasswordReset::class, function (PasswordReset $event): void {
            event(new NoticeRequested((int) $event->user->getAuthIdentifier(), NoticeKind::PasswordChanged));
        });
    }
}
