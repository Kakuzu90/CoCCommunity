<?php

namespace App\Domain\Users\Listeners;

use App\Domain\Users\Services\ProfileService;
use Illuminate\Auth\Events\Registered;

/**
 * Every new account gets its 1:1 profile + stats rows at registration, so the invariant holds before
 * the user's first request (specs/07). Listens on the framework's Registered event — the Users
 * module never references the Auth User model.
 */
final class CreateUserProfile
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function handle(Registered $event): void
    {
        $this->profiles->ensure((int) $event->user->getAuthIdentifier());
    }
}
