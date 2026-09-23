<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use App\Models\User;

class UpdateProfile
{
    public function handle(User $user, array $data): void
    {
        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
