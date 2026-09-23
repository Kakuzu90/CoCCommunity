<?php

declare(strict_types=1);

use App\Modules\Auth\Http\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="font-display font-extrabold text-2xl text-content tracking-tight">Welcome back</h1>
        <p class="mt-1 text-sm text-content-muted">Log in to your Clash Commons account.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-line-strong bg-surface-2 text-primary shadow-sm focus:ring-primary" name="remember">
                <span class="ms-2 text-sm text-content-muted">{{ __('Remember me') }}</span>
            </label>
        </div>

        <x-primary-button class="w-full mt-6">
            {{ __('Log in') }}
        </x-primary-button>

        <div class="mt-5 flex items-center justify-between text-sm">
            @if (Route::has('password.request'))
                <a class="text-content-muted hover:text-content focus:outline-none" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot password?') }}
                </a>
            @endif
            <a class="font-semibold text-primary hover:text-primary-hi focus:outline-none" href="{{ route('register') }}" wire:navigate>
                {{ __('Create account') }}
            </a>
        </div>
    </form>
</div>
