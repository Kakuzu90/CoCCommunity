<x-layouts.app title="Security settings">
    <div class="settings-page">
        <header class="settings-head">
            <p class="ui-eyebrow">Settings</p>
            <h1>Security</h1>
            @include('settings.partials.nav')
        </header>

        @if(session('status') === 'password-updated')<x-ui.alert tone="success">Password changed. Other sessions were signed out.</x-ui.alert>@endif

        <form method="POST" action="{{ route('settings.security.password.update') }}" class="settings-card">
            @csrf @method('PUT')
            <h2 class="settings-card-title">Change password</h2>
            <x-ui.input id="password_current" name="current_password" type="password" label="Current password" :error="$errors->first('current_password')" />
            <x-ui.input id="password_new" name="password" type="password" label="New password"
                hint="At least {{ config('accounts.password.min') }} characters.@if(config('accounts.password.check_compromised')) Passwords that have appeared in a known data breach are rejected.@endif"
                :error="$errors->first('password')" />
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password" label="Confirm new password" />
            <div class="settings-actions"><x-ui.button type="submit">Change password</x-ui.button></div>
        </form>

        <form method="POST" action="{{ route('settings.security.email.update') }}" class="settings-card">
            @csrf @method('PUT')
            <h2 class="settings-card-title">Change email</h2>
            <p class="ui-help">Current email: {{ auth()->user()->email }}. A verification link will be sent to the new address.</p>
            <x-ui.input id="email_new" name="email" type="email" label="New email" :error="$errors->first('email')" />
            <x-ui.input id="email_current_password" name="current_password" type="password" label="Current password" :error="$errors->first('current_password')" />
            <div class="settings-actions"><x-ui.button type="submit">Change email</x-ui.button></div>
        </form>
    </div>
</x-layouts.app>
