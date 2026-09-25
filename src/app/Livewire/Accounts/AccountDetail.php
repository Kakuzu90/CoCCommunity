<?php

namespace App\Livewire\Accounts;

use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\PlayerAccounts\Enums\RefreshOutcome;
use App\Domain\PlayerAccounts\Queries\AccountDetailQuery;
use App\Domain\PlayerAccounts\Services\AccountImageService;
use App\Domain\PlayerAccounts\Services\AccountSyncService;
use App\Domain\PlayerAccounts\Services\ManualAccountRefresh;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

final class AccountDetail extends Component
{
    #[Locked]
    public string $ulid;

    public string $flash = '';

    public function mount(string $ulid): void
    {
        $this->ulid = $ulid;
        $account = app(AccountDetailQuery::class)->find($ulid, auth()->id());
        abort_if($account === null, 404);
        app(AccountSyncService::class)->recordView($account->id);
    }

    public function refresh(ManualAccountRefresh $service, AccountDetailQuery $accounts): void
    {
        $this->authorize('manage-own-coc-accounts');
        $account = $accounts->find($this->ulid, auth()->id());
        abort_if($account === null || ! $account->owner, 404);

        try {
            $outcome = $service->refresh((int) auth()->id(), $account->id);
            $this->flash = $outcome === RefreshOutcome::Queued
                ? 'The game API was slow. Your account will refresh in the background.'
                : 'Game data refreshed.';
            $this->resetErrorBag('refresh');
        } catch (CocApiException $exception) {
            $this->addError('refresh', $exception->isNotFound()
                ? 'The game API could not find this player tag. Your verification is unchanged.'
                : 'The game API is unavailable. Saved account data is still available.');
        } catch (RuntimeException $exception) {
            $this->addError('refresh', $exception->getMessage());
        }
    }

    public function render(AccountDetailQuery $accounts, AccountImageService $images): View
    {
        $account = $accounts->find($this->ulid, auth()->id());
        abort_if($account === null, 404);

        return view('livewire.accounts.account-detail', [
            'account' => $account,
            'images' => $images->images($account->id),
        ])
            ->layout('components.layouts.app', [
                'title' => $account->ign.' · Player account',
                'description' => 'Clash of Clans account and progression for '.$account->ign.'.',
            ]);
    }
}
