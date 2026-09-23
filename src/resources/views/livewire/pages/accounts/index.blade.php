<?php

declare(strict_types=1);

use App\Modules\CocIntegration\Exceptions\InvalidTagException;
use App\Modules\PlayerAccounts\Actions\LinkAccount;
use App\Modules\PlayerAccounts\Actions\RequestOwnershipVerification;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Exceptions\TagAlreadyClaimedException;
use App\Modules\PlayerAccounts\Models\CocAccount;
use App\Modules\PlayerAccounts\Services\PlayerAccountService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $tag = '';

    /** @var array<int, string> account id => in-game token */
    public array $tokens = [];

    public function addAccount(LinkAccount $link): void
    {
        $this->validate(['tag' => ['required', 'string', 'max:16']]);

        $this->throttle('link:'.Auth::id(), 10);

        try {
            $link->handle(Auth::user(), $this->tag);
        } catch (InvalidTagException) {
            $this->addError('tag', 'That is not a valid Clash of Clans tag.');

            return;
        } catch (TagAlreadyClaimedException) {
            $this->addError('tag', 'That tag is already linked. Verify with your in-game token to claim it.');

            return;
        }

        $this->reset('tag');
        session()->flash('accounts.status', 'Account added. Paste your in-game API token to verify ownership.');
    }

    public function verify(int $accountId, RequestOwnershipVerification $request): void
    {
        $account = CocAccount::findOrFail($accountId);
        Gate::authorize('verify', $account);

        $token = trim($this->tokens[$accountId] ?? '');

        if ($token === '') {
            $this->addError('tokens.'.$accountId, 'Enter the API token from the game settings.');

            return;
        }

        $this->throttle('verify:'.Auth::id(), 10);

        $request->handle(Auth::id(), $account->tag, $token);

        unset($this->tokens[$accountId]);
        session()->flash('accounts.status', 'Verifying — this takes a moment. Refresh to see the result.');
    }

    /** @return \Illuminate\Support\Collection<int, CocAccount> */
    public function accounts(PlayerAccountService $service): \Illuminate\Support\Collection
    {
        return $service->linkedAccountsFor(Auth::user());
    }

    public function with(PlayerAccountService $service): array
    {
        return ['accounts' => $this->accounts($service)];
    }

    private function throttle(string $key, int $perMinute): void
    {
        abort_if(RateLimiter::tooManyAttempts($key, $perMinute), 429);
        RateLimiter::hit($key, 60);
    }
}; ?>

<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if (session('accounts.status'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-700 dark:text-green-300">
                {{ session('accounts.status') }}
            </div>
        @endif

        <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Link a Clash of Clans account</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Enter your player tag, then verify ownership with the in-game API token
                (Settings → More Settings → API Token).
            </p>

            <form wire:submit="addAccount" class="mt-4 flex items-end gap-3">
                <div class="flex-1">
                    <x-input-label for="tag" value="Player tag" />
                    <x-text-input wire:model="tag" id="tag" class="block mt-1 w-full" type="text" placeholder="#2P0YQRL8V" />
                    <x-input-error :messages="$errors->get('tag')" class="mt-2" />
                </div>
                <x-primary-button>Add</x-primary-button>
            </form>
        </div>

        <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your accounts</h2>

            @forelse ($accounts as $account)
                <div class="mt-4 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $account->ign ?? $account->tag }}
                                <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $account->tag }}</span>
                            </div>
                            @if ($account->latestSnapshot)
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    TH{{ $account->latestSnapshot->th_level }} ·
                                    {{ number_format($account->latestSnapshot->trophies) }} 🏆
                                    @if ($account->latestSnapshot->league) · {{ $account->latestSnapshot->league }} @endif
                                </div>
                            @endif
                        </div>
                        <span @class([
                            'text-xs font-semibold px-2.5 py-1 rounded-full',
                            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' => $account->state === AccountState::Verified,
                            'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $account->state === AccountState::Unverified,
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $account->state === AccountState::NeedsReverify,
                            'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' => $account->state === AccountState::Disputed,
                            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $account->state === AccountState::Suspended,
                        ])>{{ ucfirst(str_replace('_', ' ', $account->state->value)) }}</span>
                    </div>

                    @if ($account->state !== AccountState::Verified)
                        <form wire:submit="verify({{ $account->id }})" class="mt-3 flex items-end gap-3">
                            <div class="flex-1">
                                <x-input-label for="token-{{ $account->id }}" value="In-game API token" />
                                <x-text-input wire:model="tokens.{{ $account->id }}" id="token-{{ $account->id }}" class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('tokens.'.$account->id)" class="mt-2" />
                            </div>
                            <x-secondary-button type="submit">Verify</x-secondary-button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">No accounts linked yet. Add one above.</p>
            @endforelse
        </div>
    </div>
</div>
