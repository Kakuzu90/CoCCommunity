<?php

namespace App\Domain\Auth\Models;

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Notifications\ResetPasswordNotification;
use App\Domain\Auth\Notifications\VerifyEmailNotification;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Authentication identity and platform status (specs/07 `users`). Kept thin — profile data lives in
 * `profiles`. `role`, `status` and counters are never mass-assignable; they change through services.
 *
 * @property string $ulid
 * @property string $username
 * @property string $email
 * @property CarbonInterface|null $email_verified_at
 * @property UserRole $role
 * @property UserStatus $status
 * @property int $verified_accounts_count
 * @property CarbonInterface|null $last_login_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Only the fields a user supplies at registration. role/status/counters/ulid are set in code.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->ulid ??= (string) Str::ulid();
        });
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'status_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'username_changed_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /** Case-insensitive handles/emails are stored lower-cased so uniqueness holds on every driver. */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = Str::lower(trim($value));
    }

    public function setUsernameAttribute(string $value): void
    {
        $this->attributes['username'] = trim($value);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
