<?php

namespace App\Domain\Bases\Policies;

use App\Domain\Auth\Services\AccountGuard;
use App\Domain\Bases\Models\BaseLayout;
use Illuminate\Contracts\Auth\Authenticatable;

final class BaseLayoutPolicy
{
    public function create(Authenticatable $user): bool
    {
        $guard = app(AccountGuard::class);

        return $guard->hasVerifiedEmail($user)
            && $guard->canWrite($user)
            && $guard->hasVerifiedCocAccount($user);
    }

    public function view(Authenticatable $user, BaseLayout $base): bool
    {
        return $base->user_id === $user->getAuthIdentifier();
    }
}
