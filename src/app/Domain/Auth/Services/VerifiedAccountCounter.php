<?php

namespace App\Domain\Auth\Services;

use Illuminate\Support\Facades\DB;

/**
 * Maintains the denormalised `users.verified_accounts_count` that drives the verified badge (specs/07).
 * The column lives on the users table, so the Auth module owns its writes; other modules adjust it
 * through this seam rather than reaching into the users table themselves.
 */
final class VerifiedAccountCounter
{
    public function increment(int $userId): void
    {
        DB::table('users')->where('id', $userId)->increment('verified_accounts_count');
    }

    public function decrement(int $userId): void
    {
        DB::table('users')
            ->where('id', $userId)
            ->where('verified_accounts_count', '>', 0)
            ->decrement('verified_accounts_count');
    }
}
