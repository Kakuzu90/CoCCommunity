<?php

namespace Database\Seeders;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Repeatable local/testing fixture for the attach + verification browser spec (specs/13). Provisions a
 * clean, verified fixture user and clears any accounts/claims for it and for the tag the spec uses, so
 * the flow always starts from the empty state no matter how often the spec runs.
 */
final class CocAttachBrowserSeeder extends Seeder
{
    public const EMAIL = 'coc-attach-browser@example.test';

    public const PASSWORD = 'browser-fixture-passphrase';

    public const TAG_NORMALIZED = '2PP0LJQ';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \LogicException('Browser fixtures are restricted to local and testing environments.');
        }

        $user = User::query()->firstOrCreate(['email' => self::EMAIL], [
            'username' => 'coc_attach_fixture',
            'password' => self::PASSWORD,
        ]);
        $user->forceFill(['email_verified_at' => now(), 'verified_accounts_count' => 0])->save();

        DB::table('coc_account_claims')->where('user_id', $user->id)->delete();
        DB::table('coc_account_claims')->where('tag_normalized', self::TAG_NORMALIZED)->delete();
        DB::table('coc_accounts')->where('user_id', $user->id)->delete();
        DB::table('coc_accounts')->where('tag_normalized', self::TAG_NORMALIZED)->delete();
    }
}
