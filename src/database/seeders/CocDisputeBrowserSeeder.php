<?php

namespace Database\Seeders;

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Models\User;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Repeatable local/testing fixture for the ownership-dispute browser spec (specs/13 §4, §5). Sets up a
 * verified holder for a tag, a claimant who will contest it, and an admin who resolves it — then clears
 * any accounts, claims and disputes for the tag and these users so the flow always starts clean.
 */
final class CocDisputeBrowserSeeder extends Seeder
{
    public const HOLDER_EMAIL = 'coc-dispute-holder@example.test';

    public const CLAIMANT_EMAIL = 'coc-dispute-claimant@example.test';

    public const ADMIN_EMAIL = 'coc-dispute-admin@example.test';

    public const PASSWORD = 'browser-fixture-passphrase';

    public const TAG_NORMALIZED = '8G9V2L';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \LogicException('Browser fixtures are restricted to local and testing environments.');
        }

        $holder = $this->user(self::HOLDER_EMAIL, 'coc_dispute_holder');
        $claimant = $this->user(self::CLAIMANT_EMAIL, 'coc_dispute_claimant');
        $admin = $this->user(self::ADMIN_EMAIL, 'coc_dispute_admin');
        $admin->forceFill(['role' => UserRole::Admin->value])->save();

        foreach ([$holder->id, $claimant->id] as $id) {
            DB::table('coc_account_claims')->where('user_id', $id)->delete();
            DB::table('coc_accounts')->where('user_id', $id)->delete();
        }
        DB::table('coc_account_disputes')->where('tag_normalized', self::TAG_NORMALIZED)->delete();
        DB::table('coc_account_claims')->where('tag_normalized', self::TAG_NORMALIZED)->delete();
        DB::table('coc_accounts')->where('tag_normalized', self::TAG_NORMALIZED)->delete();

        DB::table('coc_accounts')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $holder->id,
            'tag' => '#'.self::TAG_NORMALIZED,
            'tag_normalized' => self::TAG_NORMALIZED,
            'status' => CocAccountStatus::Verified->value,
            'verified_at' => now(),
            'verification_method' => 'api_token',
            'ign' => 'FixtureHolder',
            'th_level' => 15,
            'trophies' => 5200,
            'is_featured' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $holder->id)->update(['verified_accounts_count' => 1]);
        DB::table('users')->where('id', $claimant->id)->update(['verified_accounts_count' => 0]);
    }

    private function user(string $email, string $username): User
    {
        $user = User::query()->firstOrCreate(['email' => $email], [
            'username' => $username,
            'password' => self::PASSWORD,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
