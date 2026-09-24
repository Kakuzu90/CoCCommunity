<?php

namespace App\Domain\Users\Services;

use App\Domain\Users\Data\PublicProfileData;
use App\Domain\Users\Enums\ProfileVisibility;
use App\Domain\Users\Models\UserStats;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** A public read query; joins across Auth and Users return data, never another module's model. */
class PublicProfileReadModel
{
    public function __construct(private readonly ProfileService $profiles) {}

    /** @return array{state: 'visible'|'private'|'members', profile: PublicProfileData|null} */
    public function find(string $username, ?int $viewerId): ?array
    {
        $row = DB::table('users')
            ->leftJoin('privacy_settings', 'privacy_settings.user_id', '=', 'users.id')
            ->where('users.username', $username)
            ->whereNull('users.deleted_at')
            ->whereIn('users.status', ['active', 'restricted'])
            ->select('users.id', 'users.username', 'users.created_at', 'users.verified_accounts_count',
                'privacy_settings.profile_visibility', 'privacy_settings.searchable')
            ->first();

        if ($row === null) {
            return null;
        }

        $visibility = ProfileVisibility::from($row->profile_visibility ?? ProfileVisibility::Public->value);
        $owner = $viewerId === (int) $row->id;

        if (! $owner && $visibility === ProfileVisibility::Private) {
            return ['state' => 'private', 'profile' => null];
        }

        if (! $owner && $visibility === ProfileVisibility::Members && $viewerId === null) {
            return ['state' => 'members', 'profile' => null];
        }

        $stats = UserStats::query()->findOrFail((int) $row->id);

        return ['state' => 'visible', 'profile' => new PublicProfileData(
            $row->username,
            CarbonImmutable::parse($row->created_at),
            $row->verified_accounts_count > 0,
            $this->profiles->getByUserId((int) $row->id),
            $stats->bases_published,
            $stats->total_base_likes,
            $stats->total_base_copies,
            (bool) ($row->searchable ?? true) && $visibility === ProfileVisibility::Public,
        )];
    }
}
