@props(['account', 'variant' => 'standard', 'href' => null])
@php
    if (! in_array($variant, ['hero', 'standard', 'compact', 'mini'], true)) {
        throw new InvalidArgumentException('Unknown PlayerCard variant.');
    }
    $href ??= route('accounts.show', $account->ulid);
    $label = $account->ign.' · Town Hall '.$account->thLevel.' · '.$account->status->label();
    $tier = collect(config('coc.th_tiers'))->first(fn (int $tier, int $max): bool => $account->thLevel <= $max) ?? config('coc.th_top_tier');
@endphp
@if($variant === 'hero')
@php
    $stat = fn (string $key): ?int => $account->stats[$key]['value'] ?? null;
    $trophyDelta = $account->stats['trophies']['delta'] ?? null;
@endphp
<article {{ $attributes->class('player-card player-card--hero') }} data-state="{{ $account->stale ? 'stale' : $account->status->value }}" data-th-tier="{{ $tier }}">
    <div class="player-hero">
        <section class="player-hero__panel player-hero__identity" aria-label="Player">
            <div class="player-hero__who">
                @if($stat('xp_level') !== null)
                    <span class="player-hero__xp" role="img" aria-label="XP level {{ $stat('xp_level') }}"><span>{{ $stat('xp_level') }}</span></span>
                @endif
                <div class="player-card__name">
                    <a href="{{ $href }}" aria-label="{{ $label }}">{{ $account->ign }}</a>
                    <span class="account-tag">{{ $account->tag }}</span>
                    @if($account->clanRoleLabel())<span class="player-hero__role">{{ $account->clanRoleLabel() }}</span>@endif
                </div>
            </div>
            <div class="player-card__badges">
                <span class="player-card__th" aria-label="Town Hall {{ $account->thLevel }}">
                    <x-game.asset type="townhall" :value="$account->thLevel" :size="32" />
                    <span class="player-card__th-number">TH {{ $account->thLevel }}</span>
                </span>
                @if($account->featured)<x-ui.badge variant="featured">Featured</x-ui.badge>@endif
                <x-ui.pill :tone="match ($account->status->value) { 'verified' => 'primary', 'disputed' => 'warning', 'suspended' => 'danger', default => 'neutral' }">{{ $account->status->label() }}</x-ui.pill>
            </div>
        </section>

        <section class="player-hero__panel player-hero__clan" aria-label="Clan">
            @if($account->clanTag)
                <span class="player-hero__clan-name">{{ $account->clanName ?? $account->clanTag }}</span>
                <x-game.asset type="clan" :value="$account->clanBadgeUrl" :name="$account->clanName ?? $account->clanTag" :size="88" />
                @if($account->clanLevel)<span class="player-hero__clan-level">Clan level {{ $account->clanLevel }}</span>@endif
            @elseif(! $account->clanShared)
                <span class="player-hero__muted">Clan not shared</span>
            @else
                <span class="player-hero__muted">Not in a clan</span>
            @endif
            @if($stat('war_stars') !== null)
                <div class="player-hero__war">
                    <span>War stars won</span>
                    <strong><x-ui.icon name="star" size="16" />{{ number_format($stat('war_stars')) }}</strong>
                </div>
            @endif
        </section>

        <section class="player-hero__panel player-hero__leagues" aria-label="League">
            <div class="player-hero__league">
                <x-game.asset type="league" :value="$account->leagueId ?? 0" :name="$account->leagueName ?? 'Unranked'" :size="56" />
                <div>
                    <span class="player-hero__label">Current league</span>
                    <strong>{{ $account->leagueName ?? 'Unranked' }}</strong>
                    @if($stat('trophies') !== null)
                        <span class="player-hero__trophies">{{ number_format($stat('trophies')) }} trophies</span>
                        @if($trophyDelta)<span class="player-hero__delta">{{ $trophyDelta > 0 ? '+' : '' }}{{ number_format($trophyDelta) }} since last update</span>@endif
                    @endif
                </div>
            </div>
            @if($stat('best_trophies'))
                <div class="player-hero__league">
                    <span class="player-hero__best" aria-hidden="true"><x-ui.icon name="star" size="24" /></span>
                    <div>
                        <span class="player-hero__label">All-time best</span>
                        <span class="player-hero__trophies">{{ number_format($stat('best_trophies')) }} trophies</span>
                    </div>
                </div>
            @endif
        </section>
    </div>

    <div class="player-hero__bar">
        <dl>
            <div><dt>Troops donated</dt><dd>{{ number_format($stat('donations') ?? 0) }}</dd></div>
            <div><dt>Troops received</dt><dd>{{ number_format($stat('donations_received') ?? 0) }}</dd></div>
        </dl>
        <p class="player-hero__synced">@if($account->stale)Saved data · @endif{{ $account->syncedAge ? 'Updated '.$account->syncedAge : 'Not synced yet' }}</p>
    </div>
</article>
@else
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
@endif
