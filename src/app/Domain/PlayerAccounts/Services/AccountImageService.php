<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\Media\Contracts\MediaLibrary;
use App\Domain\Media\Data\MediaImage;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AccountImageService
{
    public function __construct(private readonly MediaLibrary $media) {}

    /** @return list<MediaImage> */
    public function images(int $accountId): array
    {
        $account = CocAccount::query()->findOrFail($accountId);

        return $this->media->imagesFor($account, MediaCollection::AccountImage);
    }

    public function add(Authenticatable $user, string $accountUlid, string $mediaUlid): void
    {
        DB::transaction(function () use ($user, $accountUlid, $mediaUlid): void {
            $account = $this->owned($user, $accountUlid);
            $limit = (int) config('media.account_images_limit');
            if ($account->images_count >= $limit) {
                throw ValidationException::withMessages(['media_ulid' => "This account already has {$limit} images."]);
            }
            $this->media->attach($user, $mediaUlid, MediaCollection::AccountImage, $account);
            $account->forceFill(['images_count' => $account->images_count + 1])->save();
        });
    }

    public function remove(Authenticatable $user, string $accountUlid, string $mediaUlid): void
    {
        DB::transaction(function () use ($user, $accountUlid, $mediaUlid): void {
            $account = $this->owned($user, $accountUlid);
            abort_unless($this->media->releaseAttached($mediaUlid, MediaCollection::AccountImage, $account), 404);
            $account->forceFill(['images_count' => max(0, $account->images_count - 1)])->save();
        });
    }

    private function owned(Authenticatable $user, string $ulid): CocAccount
    {
        return CocAccount::query()->where('ulid', $ulid)
            ->where('user_id', $user->getAuthIdentifier())
            ->where('status', '!=', CocAccountStatus::Released->value)
            ->lockForUpdate()->firstOrFail();
    }
}
