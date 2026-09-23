<?php

declare(strict_types=1);

use App\Modules\Auth\Actions\Logout;
use App\Modules\Users\Actions\DeleteUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component
{
    #[Validate('required|string|current_password')]
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(DeleteUser $delete, Logout $logout): void
    {
        $this->authorize('delete', Auth::user());
        $this->validate();

        $delete->handle(Auth::user());
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-display font-bold text-content">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-content-muted">
            {{ __('Deleting your account disables sign-in. Your account record is retained for safety and audit purposes.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">

            <h2 class="text-lg font-display font-bold text-content">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-content-muted">
                {{ __('Please enter your password to confirm that you want to delete your account and disable sign-in.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
