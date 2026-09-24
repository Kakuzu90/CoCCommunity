<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Models\User;
use Illuminate\Auth\Events\Verified;

/**
 * Verifies an email from a signed link (specs/04 §4). The signature is checked by route middleware;
 * this confirms the hash matches the account's email and marks it verified, idempotently.
 */
final class EmailVerifier
{
    public function verify(string $id, string $hash): bool
    {
        $user = User::query()->find($id);
        if ($user === null || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return true;
    }
}
