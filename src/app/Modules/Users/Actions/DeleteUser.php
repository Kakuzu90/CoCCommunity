<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use App\Models\User;
use App\Modules\Users\Events\UserDeleted;
use Illuminate\Support\Facades\DB;

class DeleteUser
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->delete();
            event(new UserDeleted($user->id, $user->email));
        });
    }
}
