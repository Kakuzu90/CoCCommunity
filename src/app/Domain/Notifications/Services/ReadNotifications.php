<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\Data\NotificationData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class ReadNotifications
{
    public function __construct(private readonly NotificationReadModel $inbox) {}

    public function one(Authenticatable $user, string $id): NotificationData
    {
        Gate::forUser($user)->authorize('manage-own-notifications');
        $userId = (int) $user->getAuthIdentifier();
        $notice = $this->inbox->find($userId, $id);
        $this->inbox->owned($userId)->where('id', $id)->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);
        $this->inbox->invalidate($userId);

        return $notice;
    }

    public function all(Authenticatable $user): void
    {
        Gate::forUser($user)->authorize('manage-own-notifications');
        $userId = (int) $user->getAuthIdentifier();
        $this->inbox->owned($userId)->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);
        $this->inbox->invalidate($userId);
    }
}
