<?php

namespace Database\Factories;

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'username' => fake()->unique()->userName().Str::random(3),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            // Set explicitly (not left to the DB default) so the in-memory model matches the row and
            // role/status are never null before a refresh — gates and policies read them directly.
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => UserStatus::Suspended->value]);
    }

    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => ['status' => UserStatus::Restricted->value]);
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => ['status' => UserStatus::Banned->value]);
    }

    public function pendingDeletion(): static
    {
        return $this->state(fn (array $attributes) => ['status' => UserStatus::PendingDeletion->value]);
    }

    public function role(UserRole $role): static
    {
        return $this->state(fn (array $attributes) => ['role' => $role->value]);
    }

    public function moderator(): static
    {
        return $this->role(UserRole::Moderator);
    }

    public function admin(): static
    {
        return $this->role(UserRole::Admin);
    }

    public function superAdmin(): static
    {
        return $this->role(UserRole::SuperAdmin);
    }

    /** Gives the account a verified in-game account so the coc.verified write-gate lets it through. */
    public function withVerifiedCocAccount(): static
    {
        return $this->state(fn (array $attributes) => ['verified_accounts_count' => 1]);
    }
}
