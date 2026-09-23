<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Models\Notification;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Public entry point for in-app notifications. Email and other channels can be
 * layered on later without changing callers.
 */
class Notifier
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function push(int $userId, string $type, array $data = []): void
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
        ]);
    }

    /**
     * Notifications of the given types for a user since a moment — used by UIs
     * polling for the outcome of a queued action.
     *
     * @param  array<int, string>  $types
     * @return Collection<int, Notification>
     */
    public function recent(int $userId, array $types, DateTimeInterface $since): Collection
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->whereIn('type', $types)
            ->where('created_at', '>=', $since)
            ->latest()
            ->get();
    }
}
