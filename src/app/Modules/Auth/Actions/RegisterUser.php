<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    public function handle(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = User::create($data);
            $user->assignRole(Role::User);

            return $user;
        });

        event(new Registered($user));

        return $user;
    }
}
