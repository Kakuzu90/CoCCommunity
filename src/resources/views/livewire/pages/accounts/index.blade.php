<?php

declare(strict_types=1);

use App\Modules\CocIntegration\Exceptions\InvalidTagException;
use App\Modules\Notifications\Services\Notifier;
use App\Modules\PlayerAccounts\Actions\LinkAccount;
use App\Modules\PlayerAccounts\Actions\RequestOwnershipVerification;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Exceptions\TagAlreadyClaimedException;
use App\Modules\PlayerAccounts\Models\CocAccount;
use App\Modules\PlayerAccounts\Services\PlayerAccountService;
use Illuminate\Support\Carbon;
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

    /** @var array<int, int> account id => unix time the verification was queued */
    public array $verifying = [];

    /** @var array<int, string> account id => inline failure message */
    public array $failed = [];

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

        unset($this->tokens[$accountId], $this->failed[$accountId]);
        $this->verifying[$accountId] = now()->timestamp;
    }

    /**
     * Polled while any account is verifying: resolve each to verified, failed,
     * or timed-out so the UI updates without a manual refresh.
     */
    public function tick(Notifier $notifier, PlayerAccountService $service): void
    {
        $accounts = $service->linkedAccountsFor(Auth::user())->keyBy('id');

        foreach (array_keys($this->verifying) as $id) {
            $account = $accounts->get($id);
            $queuedAt = $this->verifying[$id];

            if ($account === null) {
                unset($this->verifying[$id]);

                continue;
            }

            if ($account->state === AccountState::Verified) {
                unset($this->verifying[$id]);
                session()->flash('accounts.status', ($account->ign ?? $account->tag).' is verified.');

                continue;
            }

            $failure = $notifier->recent(Auth::id(), ['coc.verification_failed'], Carbon::createFromTimestamp($queuedAt))
                ->first(fn ($n) => ($n->data['tag'] ?? null) === $account->tag);

            if ($failure !== null) {
                unset($this->verifying[$id]);
                $this->failed[$id] = 'Verification failed — check the token and try again.';

                continue;
            }

            if (now()->timestamp - $queuedAt > 45) {
                unset($this->verifying[$id]);
                $this->failed[$id] = 'Still working. Give it a moment and try again.';
            }
        }
    }

    public function with(PlayerAccountService $service): array
    {
        return ['accounts' => $service->linkedAccountsFor(Auth::user())];
    }

    private function throttle(string $key, int $perMinute): void
    {
        abort_if(RateLimiter::tooManyAttempts($key, $perMinute), 429);
        RateLimiter::hit($key, 60);
    }
}; ?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-6">

    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Player accounts</p>
        <h1 class="mt-1 font-display font-extrabold text-2xl sm:text-3xl text-content tracking-tight">Your Clash of Clans accounts</h1>
        <p class="mt-1 text-sm text-content-muted">Link accounts by tag, then verify ownership with the in-game API token.</p>
    </div>

    @if (session('accounts.status'))
        <div class="flex items-start gap-2.5 rounded-xl bg-verified-soft text-verified px-4 py-3 text-sm">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
            <span>{{ session('accounts.status') }}</span>
        </div>
    @endif

    <!-- Link a new account -->
    <div class="rounded-2xl bg-surface border border-line shadow-sm p-6">
        <h2 class="font-display font-bold text-content">Link an account</h2>
        <p class="mt-1 text-sm text-content-muted">Find your token in-game: Settings → More Settings → API Token.</p>

        <form wire:submit="addAccount" class="mt-4 flex items-end gap-3">
            <div class="flex-1">
                <x-input-label for="tag" value="Player tag" />
                <x-text-input wire:model="tag" id="tag" class="block mt-1 w-full" type="text" placeholder="#2P0YQRL8V" autocomplete="off" autocapitalize="characters" />
                <x-input-error :messages="$errors->get('tag')" class="mt-2" />
            </div>
            <x-primary-button wire:target="addAccount" wire:loading.attr="disabled">
                <svg wire:loading wire:target="addAccount" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <span>Add</span>
            </x-primary-button>
        </form>
    </div>

    <!-- Accounts list -->
    <div class="space-y-3">
        @forelse ($accounts as $account)
            @php($isVerifying = isset($verifying[$account->id]))
            <div class="rounded-2xl bg-surface border border-line shadow-sm p-4 sm:p-5">
                <div class="flex items-center gap-4">
                    <div class="grid place-items-center w-12 h-12 rounded-xl bg-primary text-on-primary font-display font-bold text-xs leading-none text-center">
                        <span>TH<span class="block text-base">{{ $account->latestSnapshot?->th_level ?? '?' }}</span></span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-display font-bold text-content truncate">{{ $account->ign ?? 'Unnamed' }}</span>
                            <span class="text-sm text-content-faint tabular-nums">{{ $account->tag }}</span>
                        </div>
                        <div class="mt-0.5 text-sm text-content-muted flex items-center gap-2 flex-wrap">
                            @if ($account->latestSnapshot)
                                <span class="tabular-nums">{{ number_format($account->latestSnapshot->trophies) }} 🏆</span>
                                @if ($account->latestSnapshot->league)
                                    <span class="text-content-faint">·</span><span>{{ $account->latestSnapshot->league }}</span>
                                @endif
                            @else
                                <span class="text-content-faint">No snapshot yet</span>
                            @endif
                        </div>
                    </div>

                    @if ($isVerifying)
                        <span class="shrink-0 inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-primary-soft text-primary">
                            <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            Verifying
                        </span>
                    @else
                        <span @class([
                            'shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full',
                            'bg-verified-soft text-verified' => $account->state === AccountState::Verified,
                            'bg-surface-3 text-content-muted' => $account->state === AccountState::Unverified,
                            'bg-accent-soft text-accent' => $account->state === AccountState::NeedsReverify,
                            'bg-primary-soft text-primary' => $account->state === AccountState::Disputed,
                            'bg-alert-soft text-alert' => $account->state === AccountState::Suspended,
                        ])>{{ ucfirst(str_replace('_', ' ', $account->state->value)) }}</span>
                    @endif
                </div>

                @if ($isVerifying)
                    <div class="mt-4 pt-4 border-t border-line flex items-center gap-2 text-sm text-content-muted">
                        <svg class="w-4 h-4 animate-spin text-primary" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        Checking your token with Clash of Clans…
                    </div>
                @elseif ($account->state !== AccountState::Verified)
                    <form wire:submit="verify({{ $account->id }})" class="mt-4 pt-4 border-t border-line flex items-end gap-3">
                        <div class="flex-1">
                            <x-input-label for="token-{{ $account->id }}" value="In-game API token" />
                            <x-text-input wire:model="tokens.{{ $account->id }}" id="token-{{ $account->id }}" class="block mt-1 w-full" type="text" placeholder="Paste token to verify" autocomplete="off" />
                            <x-input-error :messages="$errors->get('tokens.'.$account->id)" class="mt-2" />
                            @if (isset($failed[$account->id]))
                                <p class="mt-2 text-sm text-alert">{{ $failed[$account->id] }}</p>
                            @endif
                        </div>
                        <x-secondary-button type="submit" wire:target="verify({{ $account->id }})" wire:loading.attr="disabled">Verify</x-secondary-button>
                    </form>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-line-strong p-10 text-center">
                <div class="mx-auto grid place-items-center w-12 h-12 rounded-xl bg-surface-2 text-content-faint">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V5z"/></svg>
                </div>
                <p class="mt-3 font-display font-bold text-content">No accounts linked yet</p>
                <p class="text-sm text-content-muted">Add your player tag above to get started.</p>
            </div>
        @endforelse
    </div>

    {{-- Poll only while something is verifying; stops on its own once resolved. --}}
    @if ($verifying)
        <div wire:poll.1500ms="tick"></div>
    @endif
</div>
