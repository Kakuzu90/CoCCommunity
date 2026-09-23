<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Models\Notification;

/**
 * Public entry point for writing in-app notifications. Email and other
 * channels can be layered on later without changing callers.
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
}
