<x-layouts.app title="Active sessions">
    <div class="settings-page">
        <header class="settings-head">
            <p class="ui-eyebrow">Settings</p>
            <h1>Active sessions</h1>
            <p class="ui-help">Sign out devices you no longer use. Locations are not available yet; the IP shown is visible only to you.</p>
            @include('settings.partials.nav')
        </header>

        @if(session('status'))<x-ui.alert tone="success">Sessions updated.</x-ui.alert>@endif

        <section class="settings-card">
            <h2 class="settings-card-title">Devices</h2>
            @forelse($sessions as $entry)
                <div class="settings-session">
                    <div>
                        <strong>{{ $entry->device }}</strong> @if($entry->current)<span>(this device)</span>@endif
                        <p class="ui-help">{{ $entry->ipAddress ?: 'IP unavailable' }} · Last active {{ $entry->lastActive->diffForHumans() }}</p>
                    </div>
                    @unless($entry->current)
                        <form method="POST" action="{{ route('settings.sessions.destroy', $entry->id) }}">
                            @csrf @method('DELETE')
                            <x-ui.button type="submit" variant="secondary" size="sm">Sign out</x-ui.button>
                        </form>
                    @endunless
                </div>
            @empty
                <p class="ui-help">This session will appear after your next request.</p>
            @endforelse
        </section>

        <form method="POST" action="{{ route('settings.sessions.destroy-others') }}" class="settings-card">
            @csrf @method('DELETE')
            <h2 class="settings-card-title">Other devices</h2>
            <p class="ui-help">Keep this session and sign out everywhere else.</p>
            <div class="settings-actions"><x-ui.button type="submit" variant="secondary">Sign out other devices</x-ui.button></div>
        </form>
        <form method="POST" action="{{ route('settings.sessions.destroy-all') }}" class="settings-card">
            @csrf @method('DELETE')
            <h2 class="settings-card-title">All devices</h2>
            <p class="ui-help">Sign out this device and every other session.</p>
            <div class="settings-actions"><x-ui.button type="submit" variant="secondary">Sign out everywhere</x-ui.button></div>
        </form>
    </div>
</x-layouts.app>
