<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Notifications\OldEmailChangedNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CredentialService
{
    public function __construct(private readonly SessionService $sessions) {}

    public function changePassword(Authenticatable $principal, string $password, string $currentSessionId): void
    {
        $user = $this->user($principal);
        $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
        $this->sessions->revokeOthers($user, $currentSessionId);
    }

    public function changeEmail(Authenticatable $principal, string $email, string $currentSessionId): void
    {
        $user = $this->user($principal);
        $old = $user->email;
        $user->forceFill([
            'email' => $email,
            'email_verified_at' => null,
            'remember_token' => Str::random(60),
        ])->save();
        $this->sessions->revokeOthers($user, $currentSessionId);
        Notification::route('mail', $old)->notify(new OldEmailChangedNotification);
        $user->sendEmailVerificationNotification();
    }

    private function user(Authenticatable $principal): User
    {
        if (! $principal instanceof User) {
            throw new \LogicException('A platform user is required.');
        }

        return $principal;
    }
}
