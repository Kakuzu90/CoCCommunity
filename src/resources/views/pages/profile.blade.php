@if($state !== 'visible')
    <x-layouts.app title="Profile unavailable" robots="noindex, nofollow">
        <x-ui.empty-state :title="$state === 'private' ? 'This profile is private' : 'This profile is for members'"
            :body="$state === 'members' ? 'Sign in to view this profile.' : 'This member has chosen not to share their profile.'" />
    </x-layouts.app>
@else
    @php
        $name = $profile->profile->displayName ?: $profile->username;
        $country = $profile->profile->countryCode;
        $countryName = $country && class_exists(\Locale::class) ? \Locale::getDisplayRegion('-'.$country, 'en') : $country;
        $highestTh = $accounts->highestThLevel;
        $thTier = $highestTh === null ? null
            : (collect(config('coc.th_tiers'))->first(fn (int $tier, int $max): bool => $highestTh <= $max) ?? config('coc.th_top_tier'));
    @endphp
    <x-layouts.app :title="$name.' (@'.$profile->username.')'"
        :description="'Clash Commons profile for @'.$profile->username" :robots="$profile->searchable ? null : 'noindex, nofollow'">
        <div class="profile-page">
            <header class="profile-cover" @if($isOwner) data-owner @endif>
                {{-- Decorative: the h1 beside it already names the person, so screen readers hear it once. --}}
                <x-ui.avatar :name="$name" :src="$profile->profile->avatar?->url('card')" size="96" :verified="$profile->verified" decorative />
                <div class="profile-cover__text">
                    <h1>{{ $name }}</h1>
                    <p class="profile-cover__handle">{{ '@'.$profile->username }}</p>
                    <div class="profile-cover__meta">
                        @if($profile->verified)<x-player.verified-badge />@endif
                        <span>Member since {{ $profile->joinedAt->format('F Y') }}</span>
                        @if($countryName)<span>{{ $countryName }}</span>@endif
                    </div>
                    @if($profile->profile->bio)<p class="profile-cover__bio">{{ $profile->profile->bio }}</p>@endif
                </div>

                @if($accounts->verifiedCount > 0)
                    <div class="profile-cover__summary">
                        <span class="profile-th" data-th-tier="{{ $thTier }}">
                            <x-game.asset type="townhall" :value="$highestTh" :size="32" />
                            <span class="profile-th__number" aria-hidden="true">TH {{ $highestTh }}</span>
                            <span class="profile-th__label">Highest Town Hall</span>
                        </span>
                        <dl class="profile-summary">
                            <x-ui.stat-block :value="$accounts->verifiedCount" :label="$accounts->verifiedCount === 1 ? 'Verified account' : 'Verified accounts'" />
                            {{-- With one account the total equals the featured card's figure, so it only shows for two or more. --}}
                            @if($accounts->verifiedCount > 1)
                                <x-ui.stat-block :value="$accounts->warStars" label="War stars, all accounts" count-up />
                            @endif
                        </dl>
                    </div>
                @endif

                @if($isOwner)
                    <div class="profile-cover__actions">
                        <a class="ui-button" data-variant="secondary" data-size="sm" href="{{ route('settings.profile.edit') }}">Edit profile</a>
                        <a class="ui-button" data-variant="ghost" data-size="sm" href="{{ route('settings.privacy.edit') }}">Privacy settings</a>
                    </div>
                @endif
            </header>

            @if($accounts->featured)
                <section class="profile-featured" aria-label="Featured account">
                    <x-player.card :account="$accounts->featured" variant="hero" />
                </section>
            @endif

            @if($basePublishing)
                <section aria-label="Base stats">
                    <dl class="profile-stats">
                        <x-ui.stat-block :value="$profile->basesPublished" label="Bases published" count-up />
                        <x-ui.stat-block :value="$profile->likesReceived" label="Likes received" count-up />
                        <x-ui.stat-block :value="$profile->copies" label="Base copies" count-up />
                    </dl>
                </section>

                <x-ui.tabs id="profile" label="Profile sections" :tabs="['accounts' => 'Accounts', 'bases' => 'Bases']" class="profile-tabs" linkable>
                    <x-slot:accounts>
                        @include('pages.partials.profile-accounts')
                    </x-slot:accounts>
                    <x-slot:bases>
                        <p class="ui-help">No published bases yet.</p>
                    </x-slot:bases>
                </x-ui.tabs>
            @else
                <section class="profile-section" aria-labelledby="profile-accounts-heading">
                    <h2 id="profile-accounts-heading">{{ $accounts->featured && $accounts->others ? 'Other accounts' : 'Accounts' }}</h2>
                    @include('pages.partials.profile-accounts')
                </section>
            @endif
        </div>
    </x-layouts.app>
@endif
