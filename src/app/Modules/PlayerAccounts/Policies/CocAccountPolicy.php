<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Policies;

use App\Models\User;
use App\Modules\PlayerAccounts\Models\CocAccount;

class CocAccountPolicy
{
    public function view(User $user, CocAccount $account): bool
    {
        return $this->owns($user, $account);
    }

    public function update(User $user, CocAccount $account): bool
    {
        return $this->owns($user, $account);
    }

    public function delete(User $user, CocAccount $account): bool
    {
        return $this->owns($user, $account);
    }

    public function verify(User $user, CocAccount $account): bool
    {
        return $this->owns($user, $account);
    }

    private function owns(User $user, CocAccount $account): bool
    {
        return ! $user->trashed() && $account->user_id === $user->id;
    }
}
