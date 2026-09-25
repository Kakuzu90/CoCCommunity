<?php

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\Notifications\AccountNotice;
use App\Domain\Notifications\Services\NotificationReadModel;
use App\Domain\Notifications\Services\PruneNotifications;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class InboxChannel
{
    public function send(Authenticatable $notifiable, AccountNotice $notification): void
    {
        $inbox = app(NotificationReadModel::class);
        DB::transaction(function () use ($notifiable, $notification, $inbox): void {
            $user = DB::table('users')->where('id', $notifiable->getAuthIdentifier())->lockForUpdate()->first();
            if ($user === null || $user->deleted_at !== null) {
                return;
            }
            DB::table('notifications')->insertOrIgnore([
                'id' => $notification->id,
                'type' => $notification->kind->value,
                'notifiable_type' => $inbox->morphType(),
                'notifiable_id' => $notifiable->getAuthIdentifier(),
                'data' => json_encode(['category' => $notification->kind->category()], JSON_THROW_ON_ERROR),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            app(PruneNotifications::class)->forUser((int) $notifiable->getAuthIdentifier());
        });
    }
}
