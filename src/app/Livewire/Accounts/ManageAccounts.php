<?php

namespace App\Livewire\Accounts;

use App\Domain\PlayerAccounts\Exceptions\AccountAttachException;
use App\Domain\PlayerAccounts\Queries\PlayerAccountQuery;
use App\Domain\PlayerAccounts\Services\AccountAttachService;
use App\Domain\PlayerAccounts\Services\AccountDetachService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The attach + token-verification surface (specs/13 §3, §6, specs/18 §6). Two-step by design: look the
 * tag up first ("Is this you?"), then verify with an in-game token. Keeping the confirmed preview in the
 * component means a token retry never re-hits the API, and the tag being verified is locked to the one
 * that was previewed. Every write re-authorizes the gate and re-checks ownership in the service.
 */
final class ManageAccounts extends Component
{
    public string $tag = '';

    public string $token = '';

    public string $password = '';

    /** @var array{tag: string, ign: string, thLevel: int, trophies: int, clanName: ?string, leagueName: ?string, conflictHolder: ?string, stale: bool}|null */
    #[Locked]
    public ?array $preview = null;

    #[Locked]
    public ?int $confirmingDetachId = null;

    public string $flash = '';

    public function lookup(AccountAttachService $service): void
    {
        $this->authorize('manage-own-coc-accounts');
        $this->validate(['tag' => ['required', 'string', 'max:20']]);
        $this->reset('token');
        $this->resetErrorBag('token');

        try {
            $preview = $service->preview($this->userId(), $this->tag);
        } catch (AccountAttachException $e) {
            $this->preview = null;
            $this->addError('tag', $e->getMessage());

            return;
        }

        $this->preview = [
            'tag' => $preview->tag,
            'ign' => $preview->ign,
            'thLevel' => $preview->thLevel,
            'trophies' => $preview->trophies,
            'clanName' => $preview->clanName,
            'leagueName' => $preview->leagueName,
            'conflictHolder' => $preview->conflictHolder,
            'stale' => $preview->stale,
        ];
    }

    public function verify(AccountAttachService $service): void
    {
        $this->authorize('manage-own-coc-accounts');
        abort_if($this->preview === null, 400);
        $this->validate(['token' => ['required', 'string', 'max:60']]);

        try {
            $result = $service->verify($this->userId(), $this->preview['tag'], $this->token);
        } catch (AccountAttachException $e) {
            $this->addError('token', $e->getMessage());

            return;
        }

        if (! $result->verified()) {
            $this->addError('token', 'That token was not accepted. In-game tokens expire after a few minutes, so open Settings in the game, copy a fresh one, and paste it here.');

            return;
        }

        $this->flash = $result->superseded
            ? 'Verified. This account is now yours, transferred with your in-game token.'
            : 'Verified. Your account is now attached.';
        $this->reset('tag', 'token', 'preview');
    }

    public function cancel(): void
    {
        $this->reset('tag', 'token', 'preview');
        $this->resetErrorBag();
    }

    public function confirmDetach(int $id): void
    {
        $this->confirmingDetachId = $id;
        $this->reset('password');
        $this->resetErrorBag('password');
    }

    public function cancelDetach(): void
    {
        $this->reset('password', 'confirmingDetachId');
        $this->resetErrorBag('password');
    }

    public function detach(AccountDetachService $service): void
    {
        $this->authorize('manage-own-coc-accounts');
        abort_if($this->confirmingDetachId === null, 400);
        $this->validate(['password' => ['required', 'current_password']]);

        try {
            $service->detach($this->userId(), $this->confirmingDetachId);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        $this->flash = 'That account has been released.';
        $this->reset('password', 'confirmingDetachId');
    }

    public function render(PlayerAccountQuery $accounts): View
    {
        return view('livewire.accounts.manage-accounts', [
            'accounts' => $accounts->forUser($this->userId()),
        ])->layout('components.layouts.app', [
            'title' => 'My accounts',
            'description' => 'Attach and verify your Clash of Clans accounts.',
        ]);
    }

    private function userId(): int
    {
        return (int) auth()->id();
    }
}
