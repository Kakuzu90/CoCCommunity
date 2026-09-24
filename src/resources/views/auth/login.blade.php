<x-layouts.auth title="Sign in" description="Sign in to Clash Commons.">
    <x-ui.card class="auth-card">
        <h1 class="auth-title">Welcome back</h1>

        @include('auth.partials.status')

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf
            <x-ui.input id="email" name="email" type="email" label="Email" autocomplete="email" required
                :value="old('email')" :error="$errors->first('email')" />
            <x-ui.input id="password" name="password" type="password" label="Password"
                autocomplete="current-password" required :error="$errors->first('password')" />

            <div class="auth-row">
                <x-ui.checkbox id="remember" name="remember" label="Remember me" value="1" />
                <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
            </div>

            <x-ui.button type="submit" :block="true">Sign in</x-ui.button>
        </form>

        <p class="auth-alt">New here? <a href="{{ route('register') }}">Create an account</a>.</p>
    </x-ui.card>
</x-layouts.auth>
