<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

class SendVerificationEmail
{
    public function handle(User $user): void
    {
        abort_unless(RateLimiter::attempt('verification:'.$user->id, 6, function () use ($user): void {
            $user->sendEmailVerificationNotification();
        }), 429);
    }
}
