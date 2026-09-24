<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Model-touching auth operations kept out of the controller (Deptrac: Presentation reaches the Auth
 * module through Services, never its Eloquent model). Login status gating, login bookkeeping and the
 * password-reset completion (specs/04 §4, specs/11) live here.
 */
final class AccountAuthService
{
    /** A user-facing reason the account may not start a session, or null when it may. */
    public function lockReason(Authenticatable $user): ?string
    {
        if (! $user instanceof User) {
            return null;
        }

        return $user->status->canAuthenticate() ? null : $user->status->lockedMessage();
    }

    public function recordLogin(Authenticatable $user, ?string $ip): void
    {
        if (! $user instanceof User) {
            return;
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip_hash' => $ip === null ? null : hash('sha256', $ip),
        ])->save();
    }

    /** Set a new password, cycle the remember token and revoke every session (specs/11). */
    public function completePasswordReset(Authenticatable $user, string $password): void
    {
        if (! $user instanceof User) {
            return;
        }

        $user->forceFill([
            'password' => $password, // hashed by the model cast
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')->where('user_id', $user->getKey())->delete();

        event(new PasswordReset($user));
    }
}
