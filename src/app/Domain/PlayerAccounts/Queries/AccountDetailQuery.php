<?php

namespace App\Domain\PlayerAccounts\Queries;

use App\Domain\PlayerAccounts\Data\AccountDetailData;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\CocAccountSnapshot;
use App\Domain\PlayerAccounts\Models\SyncState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AccountDetailQuery
{
    public function find(string $ulid, ?int $viewerId): ?AccountDetailData
    {
        $account = CocAccount::query()->where('ulid', $ulid)->whereNotNull('user_id')
            ->where('status', '!=', CocAccountStatus::Released->value)->first();

        if ($account === null) {
            return null;
        }

        return $this->visible($account, $viewerId) ? $this->make($account, $viewerId) : null;
    }

    /** @return list<AccountDetailData> */
    public function forProfile(int $userId, ?int $viewerId): array
    {
        $accounts = CocAccount::query()->where('user_id', $userId)
            ->when($viewerId === $userId,
                fn ($query) => $query->where('status', '!=', CocAccountStatus::Released->value),
                fn ($query) => $query->where('status', CocAccountStatus::Verified->value))
            ->orderByDesc('is_featured')->orderByDesc('verified_at')->get();

        return array_values($accounts->filter(fn (CocAccount $account): bool => $this->visible($account, $viewerId))
            ->map(fn (CocAccount $account): AccountDetailData => $this->make($account, $viewerId))->all());
    }

    private function visible(CocAccount $account, ?int $viewerId): bool
    {
        if ($viewerId === (int) $account->user_id) {
            return true;
        }
        if ($account->status !== CocAccountStatus::Verified) {
            return false;
        }

        $owner = DB::table('users')->leftJoin('privacy_settings', 'privacy_settings.user_id', '=', 'users.id')
            ->where('users.id', $account->user_id)->whereNull('users.deleted_at')
            ->whereIn('users.status', ['active', 'restricted'])
            ->select('privacy_settings.profile_visibility', 'privacy_settings.show_coc_accounts')
            ->first();

        return $owner !== null
            && (bool) ($owner->show_coc_accounts ?? true)
            && ($owner->profile_visibility ?? 'public') !== 'private'
            && (($owner->profile_visibility ?? 'public') !== 'members' || $viewerId !== null);
    }

    private function make(CocAccount $account, ?int $viewerId): AccountDetailData
    {
        $snapshots = CocAccountSnapshot::query()->where('coc_account_id', $account->id)
            ->orderByDesc('captured_at')->orderByDesc('id')->limit(2)->get();
        $previous = $snapshots->get(1);
        $stats = [];
        foreach (['trophies', 'war_stars', 'xp_level', 'best_trophies', 'attack_wins', 'defense_wins', 'donations'] as $key) {
            $value = (int) $account->{$key};
            $stats[$key] = ['value' => $value, 'delta' => $previous === null ? null : $value - (int) $previous->{$key}];
        }

        $progression = [];
        foreach (['heroes' => 'Heroes', 'troops' => 'Troops', 'spells' => 'Spells', 'hero_equipment' => 'Equipment'] as $column => $label) {
            $progression[$label] = [];
            foreach ($account->{$column} ?? [] as $raw) {
                if (! is_array($raw)) {
                    continue;
                }
                $name = (string) ($raw['name'] ?? 'Unknown');
                $progression[$label][] = [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'level' => (int) ($raw['level'] ?? 0),
                    'maxLevel' => (int) ($raw['maxLevel'] ?? 0),
                ];
            }
        }

        $sync = SyncState::query()->where('resource_type', 'coc_account')
            ->where('resource_id', $account->id)->first();
        $stale = $sync !== null && ($sync->stale || $sync->consecutive_failures > 0);
        $showClan = $viewerId === (int) $account->user_id
            || (bool) (DB::table('privacy_settings')->where('user_id', $account->user_id)->value('show_clan') ?? true);

        return new AccountDetailData(
            id: $account->id, ulid: $account->ulid, tag: $account->tag, ign: $account->ign,
            thLevel: $account->th_level, status: $account->status, featured: $account->is_featured,
            clanTag: $showClan ? $account->clan_tag : null,
            clanRole: $showClan ? $account->clan_role : null,
            leagueId: $account->league_id, leagueName: $account->league_name,
            syncedAtIso: $account->api_synced_at?->toIso8601String(),
            syncedAge: $account->api_synced_at?->diffForHumans(), stale: $stale,
            owner: $viewerId === (int) $account->user_id,
            imagesCount: $account->images_count,
            stats: $stats, progression: $progression,
        );
    }
}
