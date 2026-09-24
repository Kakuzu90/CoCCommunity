<x-layouts.app title="Delete account" robots="noindex, nofollow">
    <div class="settings-page">
        <header class="settings-head">
            <p class="ui-eyebrow">Settings</p>
            <h1>Delete account</h1>
            @include('settings.partials.nav')
        </header>

        @if(session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif

        @if($deletion->pending)
            <section class="settings-card settings-danger">
                <h2 class="settings-card-title">Deletion scheduled</h2>
                <p>Your account is scheduled for deletion on {{ $deletion->scheduledAt->format('F j, Y') }}. You can cancel before then.</p>
                <form method="POST" action="{{ route('settings.deletion.cancel') }}" class="settings-danger-form">
                    @csrf @method('DELETE')
                    <x-ui.input id="cancel_password" name="current_password" type="password" label="Confirm password" :error="$errors->first('current_password')" />
                    <x-ui.button type="submit">Cancel deletion</x-ui.button>
                </form>
            </section>
        @else
            <section class="settings-card settings-danger">
                <h2 class="settings-card-title">Request account deletion</h2>
                <p>Your account will be hidden immediately. You have {{ config('accounts.deletion.grace_days') }} days to cancel before personal profile data is anonymized.</p>
                <form method="POST" action="{{ route('settings.deletion.request') }}" class="settings-danger-form">
                    @csrf
                    <x-ui.input id="delete_password" name="current_password" type="password" label="Confirm password" :error="$errors->first('current_password')" />
                    <x-ui.button type="submit" variant="danger">Request deletion</x-ui.button>
                </form>
            </section>
        @endif
    </div>
</x-layouts.app>
