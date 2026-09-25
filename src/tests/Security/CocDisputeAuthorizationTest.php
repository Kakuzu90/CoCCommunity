<?php

use App\Domain\Auth\Models\User;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Exceptions\DisputeException;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Services\DisputeResolutionService;
use App\Domain\PlayerAccounts\Services\DisputeService;
use App\Livewire\Accounts\ManageDisputes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function heldFor(int $userId, string $tag = '#2PP'): CocAccount
{
    $account = new CocAccount(['ign' => 'Holder', 'th_level' => 14, 'trophies' => 4000]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(),
        'user_id' => $userId,
        'tag' => $tag,
        'tag_normalized' => ltrim($tag, '#'),
        'status' => CocAccountStatus::Verified->value,
        'verified_at' => now(),
        'verification_method' => 'api_token',
    ])->save();

    return $account;
}

it('keeps the member disputes page and admin queue behind auth', function () {
    $this->get(route('accounts.disputes.index'))->assertRedirect(route('login'));
    $this->get(route('admin.disputes.index'))->assertRedirect(route('login'));
});

it('denies opening a dispute to users who cannot write', function () {
    $active = User::factory()->create();
    $suspended = User::factory()->suspended()->create();
    $unverifiedEmail = User::factory()->unverified()->create();

    expect(Gate::forUser($active)->allows('open-coc-dispute'))->toBeTrue()
        ->and(Gate::forUser($suspended)->allows('open-coc-dispute'))->toBeFalse()
        ->and(Gate::forUser($unverifiedEmail)->allows('open-coc-dispute'))->toBeFalse();
});

it('does not let a stranger respond to or withdraw a dispute (IDOR)', function () {
    $holder = User::factory()->create();
    heldFor($holder->id, '#2PP');
    $claimant = User::factory()->create();
    $stranger = User::factory()->create();
    $summary = app(DisputeService::class)->open($claimant, '#2PP', str_repeat('mine ', 6));

    expect(fn () => app(DisputeService::class)->withdraw($stranger->id, $summary->id))
        ->toThrow(DisputeException::class);
    expect(fn () => app(DisputeService::class)->respondByClaimant($stranger->id, $summary->id, str_repeat('x', 40)))
        ->toThrow(DisputeException::class);

    $this->assertDatabaseHas('coc_account_disputes', ['id' => $summary->id, 'status' => 'awaiting_holder']);
});

it('scopes the disputes list to the viewer', function () {
    $holder = User::factory()->create();
    heldFor($holder->id, '#2PP');
    $claimant = User::factory()->create();
    $stranger = User::factory()->create();
    app(DisputeService::class)->open($claimant, '#2PP', str_repeat('secret evidence ', 3));

    Livewire::actingAs($stranger)->test(ManageDisputes::class)
        ->assertDontSee('secret evidence')
        ->assertSee('No disputes filed');
});

it('forbids the admin queue and detail to non-admins', function () {
    $summary = (function () {
        $holder = User::factory()->create();
        heldFor($holder->id, '#2PP');

        return app(DisputeService::class)->open(User::factory()->create(), '#2PP', str_repeat('mine ', 6));
    })();

    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('admin.disputes.show', $summary->ulid))->assertForbidden();
});

it('404s an unknown dispute ulid for an admin', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.disputes.show', 'nonexistent-ulid'))->assertNotFound();
});

it('refuses an admin who is a party to the dispute', function () {
    $holder = User::factory()->admin()->create();
    heldFor($holder->id, '#2PP');
    $claimant = User::factory()->create();
    $summary = app(DisputeService::class)->open($claimant, '#2PP', str_repeat('mine ', 6));

    expect(fn () => app(DisputeResolutionService::class)->transfer($holder->id, $summary->id, 'self-serving'))
        ->toThrow(DisputeException::class);
});
