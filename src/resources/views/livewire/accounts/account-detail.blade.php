<div class="account-detail">
    <nav aria-label="Breadcrumb" class="account-detail__breadcrumb"><a href="{{ route('accounts.index') }}">Accounts</a><span aria-hidden="true">/</span><span>{{ $account->ign }}</span></nav>

    <x-player.card :account="$account" variant="hero" />

    @if($flash !== '')<x-ui.alert tone="success" role="status">{{ $flash }}</x-ui.alert>@endif
    @if(session('status') === 'image-added')<x-ui.alert tone="success" role="status">Account image added.</x-ui.alert>@endif
    @if(session('status') === 'image-removed')<x-ui.alert tone="success" role="status">Account image removed.</x-ui.alert>@endif
    @error('refresh')<x-ui.alert tone="danger" role="alert">{{ $message }}</x-ui.alert>@enderror
    @if($account->stale)
        <x-ui.alert tone="warning" role="status">Game data is temporarily unavailable — showing saved data{{ $account->syncedAge ? ' from '.$account->syncedAge : '' }}.</x-ui.alert>
    @elseif($account->status->value === 'disputed')
        <x-ui.alert tone="warning">This account is under ownership review. Its saved progression remains visible to you.</x-ui.alert>
    @elseif(!$account->status->isVerified())
        <x-ui.alert tone="info">This account is {{ strtolower($account->status->label()) }}. Verification is required before it appears publicly.</x-ui.alert>
    @endif

    <section class="account-detail__section" aria-labelledby="account-stats-heading">
        <h2 id="account-stats-heading">Player stats</h2>
        <dl class="account-detail__stats">
            @foreach(['trophies' => 'Trophies', 'best_trophies' => 'Best trophies', 'war_stars' => 'War stars', 'xp_level' => 'XP level', 'attack_wins' => 'Attack wins', 'defense_wins' => 'Defense wins', 'donations' => 'Donations'] as $key => $label)
                <div class="account-detail__stat">
                    <dt>{{ $label }}</dt>
                    <dd>{{ number_format($account->stats[$key]['value']) }}</dd>
                    @if($account->stats[$key]['delta'] !== null)
                        <span class="account-detail__delta" aria-label="Change since previous snapshot: {{ $account->stats[$key]['delta'] > 0 ? '+' : '' }}{{ $account->stats[$key]['delta'] }}">
                            {{ $account->stats[$key]['delta'] > 0 ? '+' : '' }}{{ number_format($account->stats[$key]['delta']) }} since last update
                        </span>
                    @endif
                </div>
            @endforeach
        </dl>
    </section>

    <section class="account-detail__section" aria-labelledby="account-progression-heading">
        <h2 id="account-progression-heading">Progression</h2>
        <div wire:loading wire:target="refresh" class="account-detail__loading" role="status" aria-live="polite">
            <span>Refreshing progression…</span>
            <div class="account-detail__skeleton" aria-hidden="true">
                <x-ui.skeleton variant="card" label="Refreshing progression" />
                <x-ui.skeleton variant="card" label="Refreshing progression" />
            </div>
        </div>
        <div class="account-detail__groups" wire:loading.remove wire:target="refresh">
            @foreach($account->progression as $group => $units)
                <section aria-labelledby="progression-{{ \Illuminate\Support\Str::slug($group) }}">
                    <h3 id="progression-{{ \Illuminate\Support\Str::slug($group) }}">{{ $group }}</h3>
                    @if($units)
                        <ul class="account-detail__units">
                            @foreach($units as $unit)
                                <li>
                                    <x-game.asset type="unit" :value="$unit['slug']" :name="$unit['name']" :size="32" />
                                    <span class="account-detail__unit-name">{{ $unit['name'] }}</span>
                                    <span class="account-detail__level">Level {{ $unit['level'] }} @if($unit['maxLevel'] > 0 && $unit['level'] >= $unit['maxLevel']) · Maxed @endif</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="ui-help">No {{ strtolower($group) }} data shared by the game yet.</p>
                    @endif
                </section>
            @endforeach
        </div>
    </section>

    <section class="account-detail__section" aria-labelledby="account-images-heading">
        <h2 id="account-images-heading">Account images</h2>
        @if($images)
            <ul class="account-detail__gallery">
                @foreach($images as $image)
                    <li>
                        <a href="{{ $image->url('full') }}" target="_blank" rel="noopener" aria-label="Open account image">
                            <img src="{{ $image->url('card') }}" alt="Account image for {{ $account->ign }}" loading="lazy">
                        </a>
                        @if($account->owner)
                            <form method="POST" action="{{ route('accounts.images.destroy', [$account->ulid, $image->ulid]) }}">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm">Remove image</x-ui.button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @elseif(!$account->owner)
            <p class="ui-help">No account images shared yet.</p>
        @endif
        @if($account->owner && $account->imagesCount < (int) config('media.account_images_limit'))
            <div class="account-detail__upload" x-data="accountImageUploader">
                <label class="account-detail__dropzone">
                    <span>Add an account image</span>
                    <span>JPG, PNG or WebP · up to 5 MB</span>
                    <input type="file" accept="image/jpeg,image/png,image/webp" x-on:change="pick($event)" x-bind:disabled="busy">
                </label>
                <p class="ui-help" aria-live="polite">
                    <span x-show="statusText" x-text="statusText"></span>
                    <span class="text-danger" x-show="error" x-text="error"></span>
                </p>
                <form method="POST" action="{{ route('accounts.images.store', $account->ulid) }}" x-ref="attachForm" hidden>
                    @csrf
                    <input type="hidden" name="media_ulid" x-ref="mediaInput">
                </form>
            </div>
            @error('media_ulid')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
        @endif
    </section>

    <section class="account-detail__section" aria-labelledby="account-bases-heading">
        <h2 id="account-bases-heading">Bases</h2>
        <p class="ui-help">No bases credited to this account yet.</p>
    </section>

    <footer class="account-detail__footer">
        <p>{{ $account->syncedAtIso ? 'Updated '.$account->syncedAge : 'Not synced yet' }} @if($account->stale) · Saved data shown @endif</p>
        @if($account->owner && in_array($account->status->value, ['verified', 'unverified'], true))
            <x-ui.button type="button" variant="secondary" wire:click="refresh" wire:target="refresh" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="refresh">Refresh game data</span>
                <span wire:loading wire:target="refresh">Refreshing…</span>
            </x-ui.button>
        @endif
    </footer>
</div>
