@if($state !== 'visible')
    <x-layouts.app title="Profile unavailable" robots="noindex, nofollow">
        <x-ui.empty-state :title="$state === 'private' ? 'This profile is private' : 'This profile is for members'"
            :body="$state === 'members' ? 'Sign in to view this profile.' : 'This member has chosen not to share their profile.'" />
    </x-layouts.app>
@else
    <x-layouts.app :title="($profile->profile->displayName ?: $profile->username).' (@'.$profile->username.')'"
        :description="'Clash Commons profile for @'.$profile->username" :robots="$profile->searchable ? null : 'noindex, nofollow'">
        <div class="settings-page">
            <header class="settings-head">
                <x-ui.avatar :name="$profile->profile->displayName ?: $profile->username" :src="$profile->profile->avatar?->url('card')" size="96" />
                <h1>{{ $profile->profile->displayName ?: $profile->username }}</h1>
                <p class="ui-help">{{ '@'.$profile->username }} @if($profile->verified) · Verified player @endif · Member since {{ $profile->joinedAt->format('F Y') }}</p>
                @if($profile->profile->countryCode)<p>{{ $profile->profile->countryCode }}</p>@endif
                @if($profile->profile->bio)<p>{{ $profile->profile->bio }}</p>@endif
                @auth
                    @if(auth()->user()->username === $profile->username)<p><a href="{{ route('settings.profile.edit') }}">Edit profile</a> · <a href="{{ route('settings.privacy.edit') }}">Privacy settings</a></p>@endif
                @endauth
            </header>
            <section class="settings-card" aria-label="Player stats">
                <h2 class="settings-card-title">Stats</h2>
                <div class="settings-grid">
                    <p>Bases published: {{ $profile->basesPublished }}</p>
                    <p>Likes received: {{ $profile->likesReceived }}</p>
                    <p>Copies: {{ $profile->copies }}</p>
                </div>
            </section>
            <section class="settings-card">
                <h2 class="settings-card-title">Accounts</h2>
                <p class="ui-help">No public accounts yet.</p>
            </section>
            <section class="settings-card">
                <h2 class="settings-card-title">Bases</h2>
                <p class="ui-help">No published bases yet.</p>
            </section>
        </div>
    </x-layouts.app>
@endif
