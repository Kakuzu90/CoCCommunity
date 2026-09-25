<?php

namespace Database\Seeders;

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;

/**
 * A small, stable set of usable accounts — one per role — for signing in and exercising the app locally
 * (specs/04). Idempotent: each account is created only if its email is not already present, so re-running
 * never duplicates or overwrites existing users. Every account is active, verified, and uses the password
 * "password".
 */
final class DedicatedUsersSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $this->ensure('admin@clashcommons.test', 'admin_chief', UserRole::Admin);
        $this->ensure('moderator@clashcommons.test', 'mod_scout', UserRole::Moderator);
        $this->ensure('member@clashcommons.test', 'member_barch', UserRole::User);
    }

    private function ensure(string $email, string $username, UserRole $role): void
    {
        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $factory = match ($role) {
            UserRole::Admin => User::factory()->admin(),
            UserRole::Moderator => User::factory()->moderator(),
            UserRole::SuperAdmin => User::factory()->superAdmin(),
            UserRole::User => User::factory(),
        };

        $factory->create([
            'username' => $username,
            'email' => $email,
            'password' => self::PASSWORD,
        ]);
    }
}
