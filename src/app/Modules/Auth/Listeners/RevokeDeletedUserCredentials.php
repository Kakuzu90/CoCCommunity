<?php

declare(strict_types=1);

namespace App\Modules\Auth\Listeners;

use App\Modules\Users\Events\UserDeleted;
use Illuminate\Support\Facades\DB;

class RevokeDeletedUserCredentials
{
    public function handle(UserDeleted $event): void
    {
        DB::table('sessions')->where('user_id', $event->userId)->delete();
        DB::table('password_reset_tokens')->where('email', $event->email)->delete();
    }
}
