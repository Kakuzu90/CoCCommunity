<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\DeletionData;
use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Events\AccountAnonymized;
use App\Domain\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountDeletionService
{
    public function __construct(private readonly SessionService $sessions) {}

    public function get(Authenticatable $principal): DeletionData
    {
        $user = $this->user($principal);
        $requested = $user->deletion_requested_at;

        return new DeletionData(
            $user->status === UserStatus::PendingDeletion,
            $requested === null ? null : CarbonImmutable::parse($requested),
            $requested === null ? null : CarbonImmutable::parse($requested)->addDays((int) config('accounts.deletion.grace_days')),
        );
    }

    public function request(Authenticatable $principal): void
    {
        $user = $this->user($principal);
        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'status' => UserStatus::PendingDeletion,
                'deletion_requested_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();
            $this->sessions->revokeAll($user);
        });
    }

    public function cancel(Authenticatable $principal): void
    {
        $user = $this->user($principal);
        $deadline = $user->deletion_requested_at === null
            ? null
            : CarbonImmutable::parse($user->deletion_requested_at)->addDays((int) config('accounts.deletion.grace_days'));
        abort_if($deadline === null || $deadline->isPast(), 403);

        $user->forceFill(['status' => UserStatus::Active, 'deletion_requested_at' => null])->save();
    }

    /** @return int number of accounts anonymized */
    public function anonymizeDue(): int
    {
        $cutoff = now()->subDays((int) config('accounts.deletion.grace_days'));
        $count = 0;

        User::query()->where('status', UserStatus::PendingDeletion)
            ->where('deletion_requested_at', '<=', $cutoff)
            ->orderBy('id')->chunkById((int) config('accounts.deletion.chunk_size'), function ($users) use (&$count): void {
                foreach ($users as $user) {
                    DB::transaction(function () use ($user, &$count): void {
                        $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();
                        if ($locked === null || $locked->status !== UserStatus::PendingDeletion) {
                            return;
                        }

                        $locked->forceFill([
                            'username' => 'deleted_user_'.$locked->ulid,
                            'email' => 'deleted_'.hash_hmac('sha256', $locked->email, (string) config('app.key')).'@invalid.local',
                            'password' => Str::random(60),
                            'status' => UserStatus::Banned,
                            'remember_token' => Str::random(60),
                        ])->save();
                        $locked->delete();
                        $this->sessions->revokeAll($locked);
                        DB::afterCommit(fn () => event(new AccountAnonymized($locked->id)));
                        $count++;
                    });
                }
            });

        return $count;
    }

    private function user(Authenticatable $principal): User
    {
        if (! $principal instanceof User) {
            throw new \LogicException('A platform user is required.');
        }

        return $principal;
    }
}
