<?php

namespace App\Livewire\Accounts;

use App\Domain\PlayerAccounts\Enums\HolderResponse;
use App\Domain\PlayerAccounts\Exceptions\DisputeException;
use App\Domain\PlayerAccounts\Queries\DisputeQuery;
use App\Domain\PlayerAccounts\Services\DisputeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The member's dispute surface (specs/13 §5 step 3, guardrails). Two audiences on one page: disputes I
 * filed (where I can withdraw or add requested information) and disputes filed against my accounts
 * (where I respond with a statement or hand the tag over). Every write re-authorizes and the services
 * re-check that the acting user is actually the claimant or the holder, so a forged id 404s or is
 * rejected.
 */
final class ManageDisputes extends Component
{
    #[Locked]
    public ?int $respondingId = null;

    public string $holderNote = '';

    #[Locked]
    public ?int $infoId = null;

    public string $infoNote = '';

    #[Locked]
    public ?int $withdrawingId = null;

    public string $flash = '';

    public function startRespond(int $id): void
    {
        $this->respondingId = $id;
        $this->reset('holderNote');
        $this->resetErrorBag('holderNote');
    }

    public function cancelRespond(): void
    {
        $this->reset('respondingId', 'holderNote');
        $this->resetErrorBag('holderNote');
    }

    public function submitStatement(DisputeService $service): void
    {
        $this->authorize('manage-own-coc-accounts');
        abort_if($this->respondingId === null, 400);
        $this->validate(['holderNote' => ['required', 'string', 'min:20', 'max:2000']]);

        try {
            $service->respondByHolder($this->userId(), $this->respondingId, HolderResponse::Counter, $this->holderNote);
        } catch (DisputeException $e) {
            $this->addError('holderNote', $e->getMessage());

            return;
        }

        $this->flash = 'Your statement was sent to the moderators for review.';
        $this->reset('respondingId', 'holderNote');
    }

    public function release(DisputeService $service, int $id): void
    {
        $this->authorize('manage-own-coc-accounts');

        try {
            $service->respondByHolder($this->userId(), $id, HolderResponse::Release);
        } catch (DisputeException $e) {
            $this->flash = $e->getMessage();

            return;
        }

        $this->flash = 'You released the tag. It now belongs to the claimant.';
        $this->reset('respondingId', 'holderNote');
    }

    public function startInfo(int $id): void
    {
        $this->infoId = $id;
        $this->reset('infoNote');
        $this->resetErrorBag('infoNote');
    }

    public function cancelInfo(): void
    {
        $this->reset('infoId', 'infoNote');
        $this->resetErrorBag('infoNote');
    }

    public function submitInfo(DisputeService $service): void
    {
        $this->authorize('manage-own-coc-accounts');
        abort_if($this->infoId === null, 400);
        $this->validate(['infoNote' => ['required', 'string', 'min:20', 'max:2000']]);

        try {
            $service->respondByClaimant($this->userId(), $this->infoId, $this->infoNote);
        } catch (DisputeException $e) {
            $this->addError('infoNote', $e->getMessage());

            return;
        }

        $this->flash = 'Thanks. Your dispute is back with the moderators.';
        $this->reset('infoId', 'infoNote');
    }

    public function confirmWithdraw(int $id): void
    {
        $this->withdrawingId = $id;
    }

    public function cancelWithdraw(): void
    {
        $this->reset('withdrawingId');
    }

    public function withdraw(DisputeService $service): void
    {
        $this->authorize('manage-own-coc-accounts');
        abort_if($this->withdrawingId === null, 400);

        try {
            $service->withdraw($this->userId(), $this->withdrawingId);
        } catch (DisputeException $e) {
            $this->flash = $e->getMessage();
            $this->reset('withdrawingId');

            return;
        }

        $this->flash = 'Your dispute was withdrawn.';
        $this->reset('withdrawingId');
    }

    public function render(DisputeQuery $disputes): View
    {
        return view('livewire.accounts.manage-disputes', [
            'mine' => $disputes->forClaimant($this->userId()),
            'against' => $disputes->againstHolder($this->userId()),
        ])->layout('components.layouts.app', [
            'title' => 'Disputes',
            'description' => 'Ownership disputes you filed or that concern your accounts.',
        ]);
    }

    private function userId(): int
    {
        return (int) auth()->id();
    }
}
