<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Models\User;

class UpdatePassword
{
    public function handle(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }
}
