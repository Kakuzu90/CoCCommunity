<x-layouts.auth title="Verify your email">
    <x-ui.card class="auth-card">
        <h1 class="auth-title">Verify your email</h1>
        <p class="auth-lead">We've sent a verification link to your inbox. Click it to activate writes on your account.</p>

        @include('auth.partials.status')

        <form method="POST" action="{{ route('verification.send') }}" class="auth-form">
            @csrf
            <x-ui.button type="submit" :block="true">Resend verification email</x-ui.button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="auth-alt">
            @csrf
            <button type="submit" class="auth-link">Sign out</button>
        </form>
    </x-ui.card>
</x-layouts.auth>
