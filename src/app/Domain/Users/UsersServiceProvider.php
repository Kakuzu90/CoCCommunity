<?php

namespace App\Domain\Users;

use App\Domain\Auth\Events\AccountAnonymized;
use App\Domain\Users\Listeners\AnonymizeUserProfile;
use App\Domain\Users\Listeners\CreateUserProfile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class UsersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Provision the 1:1 profile + stats rows when an account is registered (specs/07).
        Event::listen(Registered::class, CreateUserProfile::class);
        Event::listen(AccountAnonymized::class, AnonymizeUserProfile::class);
    }
}
