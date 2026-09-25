@props(['account', 'variant' => 'standard', 'href' => null])
@php
    if (! in_array($variant, ['hero', 'standard', 'compact', 'mini'], true)) {
        throw new InvalidArgumentException('Unknown PlayerCard variant.');
    }
    $href ??= route('accounts.show', $account->ulid);
    $label = $account->ign.' · Town Hall '.$account->thLevel.' · '.$account->status->label();
    $tier = collect(config('coc.th_tiers'))->first(fn (int $tier, int $max): bool => $account->thLevel <= $max) ?? config('coc.th_top_tier');
@endphp
<article {{ $attributes->class('player-card player-card--'.$variant) }} data-state="{{ $account->stale ? 'stale' : $account->status->value }}" data-th-tier="{{ $tier }}">
    <div class="player-card__top">
        <span class="player-card__th" aria-label="Town Hall {{ $account->thLevel }}">
            <x-game.asset type="townhall" :value="$account->thLevel" :size="$variant === 'mini' ? 24 : 32" />
            <span class="player-card__th-number">TH {{ $account->thLevel }}</span>
        </span>
        <span class="player-card__badges">
            @if($account->featured)<x-ui.badge variant="featured">Featured</x-ui.badge>@endif
            <x-ui.pill :tone="match ($account->status->value) { 'verified' => 'primary', 'disputed' => 'warning', 'suspended' => 'danger', default => 'neutral' }">{{ $account->status->label() }}</x-ui.pill>
        </span>
    </div>
    <div class="player-card__identity">
        <x-ui.avatar :name="$account->ign" :size="$variant === 'hero' ? 96 : ($variant === 'mini' ? 32 : 48)" :verified="$account->status->isVerified()" />
        <div class="player-card__name">
            <a href="{{ $href }}" aria-label="{{ $label }}">{{ $account->ign }}</a>
            <span class="account-tag">{{ $account->tag }}</span>
        </div>
        @if($account->leagueName && $variant !== 'mini')
            <span class="player-card__league">
                <x-game.asset type="league" :value="$account->leagueId" :name="$account->leagueName" :size="32" />
                <span>{{ $account->leagueName }}</span>
            </span>
        @endif
    </div>
    @if($variant !== 'mini')
        <dl class="player-card__stats">
            @foreach(['trophies' => 'Trophies', 'war_stars' => 'War stars', 'xp_level' => 'XP level'] as $key => $title)
                <x-ui.stat-block :value="$account->stats[$key]['value']" :label="$title" :delta="$account->stats[$key]['delta']" />
            @endforeach
        </dl>
        <div class="player-card__footer">
            @if($account->clanTag)<span>Clan {{ $account->clanTag }}{{ $account->clanRole ? ' · '.ucfirst($account->clanRole) : '' }}</span>
            @elseif(! $account->clanShared)<span>Clan not shared</span>@endif
            <span>@if($account->stale)Saved data · @endif{{ $account->syncedAge ? 'Updated '.$account->syncedAge : 'Not synced yet' }}</span>
        </div>
    @endif
</article>
