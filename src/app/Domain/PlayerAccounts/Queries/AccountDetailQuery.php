<?php

namespace App\Domain\PlayerAccounts\Queries;

use App\Domain\PlayerAccounts\Data\AccountDetailData;
use App\Domain\PlayerAccounts\Data\ProfileAccounts;
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

    /**
     * Accounts for a public profile in a fixed number of queries, whatever the account count. Others see
     * verified accounts and ones under review (specs/13 §2); the owner sees everything not released.
     * Profile cards show no deltas or progression, so neither is loaded here.
     */
    public function forProfile(int $userId, ?int $viewerId): ProfileAccounts
    {
        $owner = $viewerId === $userId;
        $privacy = $this->ownerPrivacy($userId);
        if (! $owner && ! $this->publiclyVisible($privacy, $viewerId)) {
            return ProfileAccounts::empty();
        }

        $accounts = CocAccount::query()->where('user_id', $userId)
            ->when($owner,
                fn ($query) => $query->where('status', '!=', CocAccountStatus::Released->value),
                fn ($query) => $query->whereIn('status', [CocAccountStatus::Verified->value, CocAccountStatus::Disputed->value]))
            ->orderByDesc('is_featured')->orderByDesc('verified_at')->orderByDesc('id')->get();

        $sync = SyncState::query()->where('resource_type', 'coc_account')
            ->whereIn('resource_id', $accounts->pluck('id'))
            ->get(['resource_id', 'stale', 'consecutive_failures'])->keyBy('resource_id');
        $showClan = $owner || (bool) ($privacy->show_clan ?? true);

        $cards = $accounts->map(function (CocAccount $account) use ($sync, $showClan, $owner): AccountDetailData {
            $state = $sync->get($account->id);
            $stats = [];
            foreach (['trophies', 'war_stars', 'xp_level'] as $key) {
                $stats[$key] = ['value' => (int) $account->{$key}, 'delta' => null];
            }

            return new AccountDetailData(
                id: $account->id, ulid: $account->ulid, tag: $account->tag, ign: $account->ign,
                thLevel: $account->th_level, status: $account->status, featured: $account->is_featured,
                clanTag: $showClan ? $account->clan_tag : null,
                clanRole: $showClan ? $account->clan_role : null,
                leagueId: $account->league_id, leagueName: $account->league_name,
                syncedAtIso: $account->api_synced_at?->toIso8601String(),
                syncedAge: $account->api_synced_at?->diffForHumans(),
                stale: $state !== null && ($state->stale || $state->consecutive_failures > 0),
                owner: $owner, imagesCount: $account->images_count,
                stats: $stats, progression: [],
                clanShared: $showClan || $account->clan_tag === null,
            );
        });

        $featured = $cards->first(fn (AccountDetailData $card): bool => $card->featured);
        $verified = $cards->filter(fn (AccountDetailData $card): bool => $card->status->isVerified());
        $highest = $verified->max(fn (AccountDetailData $card): int => $card->thLevel);

        return new ProfileAccounts(
            featured: $featured,
            others: array_values($cards->reject(fn (AccountDetailData $card): bool => $card === $featured)->all()),
            warStars: (int) $verified->sum(fn (AccountDetailData $card): int => $card->stats['war_stars']['value']),
            verifiedCount: $verified->count(),
            highestThLevel: $highest === null ? null : (int) $highest,
        );
    }

    private function visible(CocAccount $account, ?int $viewerId): bool
    {
        if ($viewerId === (int) $account->user_id) {
            return true;
        }
        if (! in_array($account->status, [CocAccountStatus::Verified, CocAccountStatus::Disputed], true)) {
            return false;
        }

        return $this->publiclyVisible($this->ownerPrivacy((int) $account->user_id), $viewerId);
    }

    /** The owner's privacy row, or null when the owner is deleted, suspended or banned (their accounts hide with them). */
    private function ownerPrivacy(int $userId): ?object
    {
        return DB::table('users')->leftJoin('privacy_settings', 'privacy_settings.user_id', '=', 'users.id')
            ->where('users.id', $userId)->whereNull('users.deleted_at')
            ->whereIn('users.status', ['active', 'restricted'])
            ->select('privacy_settings.profile_visibility', 'privacy_settings.show_coc_accounts', 'privacy_settings.show_clan')
            ->first();
    }

    private function publiclyVisible(?object $privacy, ?int $viewerId): bool
    {
        $visibility = $privacy->profile_visibility ?? 'public';

        return $privacy !== null
            && (bool) ($privacy->show_coc_accounts ?? true)
            && $visibility !== 'private'
            && ($visibility !== 'members' || $viewerId !== null);
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
            clanShared: $showClan || $account->clan_tag === null,
        );
    }
}
