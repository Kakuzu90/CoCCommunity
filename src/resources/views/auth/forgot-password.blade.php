<x-layouts.auth title="Reset your password">
    <x-ui.card class="auth-card">
        <h1 class="auth-title">Reset your password</h1>
        <p class="auth-lead">Enter your email and we'll send a reset link if an account exists.</p>

        @include('auth.partials.status')

        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf
            <x-ui.input id="email" name="email" type="email" label="Email" autocomplete="email" required
                :value="old('email')" :error="$errors->first('email')" />
            @include('auth.partials.turnstile')
            <x-ui.button type="submit" :block="true">Send reset link</x-ui.button>
        </form>

        <p class="auth-alt"><a href="{{ route('login') }}">Back to sign in</a>.</p>
    </x-ui.card>
</x-layouts.auth>
