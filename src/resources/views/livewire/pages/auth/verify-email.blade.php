<?php

declare(strict_types=1);

use App\Modules\Auth\Actions\Logout;
use App\Modules\Auth\Actions\SendVerificationEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(SendVerificationEmail $send): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        $this->authorize('update', Auth::user());
        $send->handle(Auth::user());

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="font-display font-extrabold text-2xl text-content tracking-tight">Verify your email</h1>
        <p class="mt-1 text-sm text-content-muted">
            {{ __('Thanks for signing up! Click the link we just emailed you to verify your address. Didn\'t get it? We\'ll send another.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl bg-verified-soft text-verified px-4 py-3 text-sm">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            {{ __('Resend Verification Email') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="underline text-sm text-content-muted hover:text-content rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            {{ __('Log Out') }}
        </button>
    </div>
</div>
