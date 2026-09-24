<x-layouts.auth title="Check your email">
    <x-ui.card class="auth-card">
        <h1 class="auth-title">Check your email</h1>
        <p class="auth-lead">If that email is available, we've sent a verification link. Click it to finish setting up your account. Already registered? The email will point you to sign in.</p>
        <p class="auth-alt"><a href="{{ route('login') }}">Go to sign in</a>.</p>
    </x-ui.card>
</x-layouts.auth>
