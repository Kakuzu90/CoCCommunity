<x-layouts.app title="Active sessions">
    @php($messages = [
        'session-revoked' => 'That device was signed out.',
        'sessions-revoked' => 'Every other device was signed out. This one is still active.',
    ])

    <div class="settings-page">
        <header class="settings-head">
            <p class="ui-eyebrow">Settings</p>
            <h1>Active sessions</h1>
            <p class="ui-help">Sign out devices you no longer use. Locations are not available yet; the IP shown is visible only to you.</p>
            @include('settings.partials.nav')
        </header>

        @if($message = $messages[session('status')] ?? null)
            <x-ui.alert tone="success">{{ $message }}</x-ui.alert>
        @endif

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

        <section class="settings-card">
            <h2 class="settings-card-title">Sign out in bulk</h2>
            <p class="ui-help">Keeping this device signs out every other session and leaves you signed in here. Signing out everywhere includes this device, so you will need to sign in again.</p>
            <div class="settings-bulk">
                <form method="POST" action="{{ route('settings.sessions.destroy-others') }}">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="secondary">Sign out other devices</x-ui.button>
                </form>
                <form method="POST" action="{{ route('settings.sessions.destroy-all') }}">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="danger">Sign out everywhere</x-ui.button>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
