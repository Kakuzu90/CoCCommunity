<x-layouts.auth title="Choose a new password">
    <x-ui.card class="auth-card">
        <h1 class="auth-title">Choose a new password</h1>

        <form method="POST" action="{{ route('password.store') }}" class="auth-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-ui.input id="email" name="email" type="email" label="Email" autocomplete="email" required
                :value="old('email', $email)" :error="$errors->first('email')" />
            <x-ui.input id="password" name="password" type="password" label="New password"
                autocomplete="new-password" required hint="At least 10 characters." :error="$errors->first('password')" />
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password"
                label="Confirm new password" autocomplete="new-password" required />
            <x-ui.button type="submit" :block="true">Reset password</x-ui.button>
        </form>
    </x-ui.card>
</x-layouts.auth>
