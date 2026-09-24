<x-layouts.auth title="Create your account" description="Join Clash Commons.">
    <x-ui.card class="auth-card">
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-lead">Prove your bases, share layouts, find a clan.</p>

        @include('auth.partials.status')

        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf
            <input type="hidden" name="form_started_at" value="{{ now()->getTimestampMs() }}">
            {{-- Honeypot: real people never fill this; bots do (specs/11). --}}
            <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px" tabindex="-1">
                <label>Company website<input type="text" name="{{ config('accounts.registration.honeypot_field') }}" tabindex="-1" autocomplete="off"></label>
            </div>

            <x-ui.input id="username" name="username" label="Username" autocomplete="username" required
                :value="old('username')" hint="3–20 letters, numbers or underscore." :error="$errors->first('username')" />
            <x-ui.input id="email" name="email" type="email" label="Email" autocomplete="email" required
                :value="old('email')" :error="$errors->first('email')" />
            <x-ui.input id="password" name="password" type="password" label="Password" autocomplete="new-password" required
                hint="At least 10 characters." :error="$errors->first('password')" />
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password"
                label="Confirm password" autocomplete="new-password" required />

            @include('auth.partials.turnstile')

            <x-ui.button type="submit" :block="true">Create account</x-ui.button>
        </form>

        <p class="auth-alt">Already have an account? <a href="{{ route('login') }}">Sign in</a>.</p>
    </x-ui.card>
</x-layouts.auth>
