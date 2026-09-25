<?php

namespace App\Livewire\Admin;

use App\Domain\PlayerAccounts\Exceptions\DisputeException;
use App\Domain\PlayerAccounts\Services\DisputeResolutionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The admin resolve panel for a single dispute (specs/13 §5 step 5). Each decision re-authorizes the
 * ability at the server — the admin-area middleware is never the sole check (specs/04 §3) — and a
 * mandatory note is required for the immutable moderation record. Transfer, the one action that moves
 * ownership, additionally requires the `force-ownership-transfer` ability. An admin who is a party to
 * the dispute is rejected by the service.
 */
final class ResolveDispute extends Component
{
    #[Locked]
    public int $disputeId;

    public string $note = '';

    public bool $resolved = false;

    public string $flash = '';

    public function mount(int $disputeId): void
    {
        $this->disputeId = $disputeId;
    }

    public function transfer(DisputeResolutionService $service): void
    {
        Gate::authorize('resolve-disputes');
        Gate::authorize('force-ownership-transfer');
        $this->run(fn () => $service->transfer($this->actorId(), $this->disputeId, $this->note), 'The tag was transferred to the claimant.');
    }

    public function deny(DisputeResolutionService $service): void
    {
        Gate::authorize('resolve-disputes');
        $this->run(fn () => $service->deny($this->actorId(), $this->disputeId, $this->note), 'The dispute was denied. The holder keeps the tag.');
    }

    public function suspend(DisputeResolutionService $service): void
    {
        Gate::authorize('resolve-disputes');
        $this->run(fn () => $service->suspend($this->actorId(), $this->disputeId, $this->note), 'The tag was suspended. Neither party holds it.');
    }

    public function requestInfo(DisputeResolutionService $service): void
    {
        Gate::authorize('resolve-disputes');
        $this->run(fn () => $service->requestMoreInfo($this->actorId(), $this->disputeId, $this->note), 'The claimant was asked for more information.');
    }

    private function run(callable $action, string $success): void
    {
        $this->validate(['note' => ['required', 'string', 'min:10', 'max:2000']]);

        try {
            $action();
        } catch (DisputeException $e) {
            $this->addError('note', $e->getMessage());

            return;
        }

        $this->resolved = true;
        $this->flash = $success;
    }

    public function render(): View
    {
        return view('livewire.admin.resolve-dispute');
    }

    private function actorId(): int
    {
        return (int) auth()->id();
    }
}
