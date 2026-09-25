@php
    $statusTone = fn ($status) => match ($status->value) {
        'resolved_transfer' => 'primary',
        'resolved_denied' => 'danger',
        'withdrawn', 'auto_resolved' => 'neutral',
        default => 'warning',
    };
@endphp
<div class="accounts-page">
    <header class="settings-head">
        <p class="ui-eyebrow">Accounts</p>
        <h1>Ownership disputes</h1>
        <p class="ui-help">The manual path for an account you own but cannot verify with a token. A moderator decides; absent decisive proof the current holder keeps the tag.</p>
    </header>

    @if($flash !== '')
        <x-ui.alert tone="success" wire:key="disputes-flash">{{ $flash }}</x-ui.alert>
    @endif

    <section class="settings-card" aria-labelledby="against-heading">
        <h2 id="against-heading">Filed against your accounts</h2>
        <p class="ui-help">Someone is claiming a tag you hold. The fastest way to end a dispute is to re-verify with a fresh in-game token on the <a href="{{ route('accounts.index') }}">Accounts</a> page.</p>

        @forelse($against as $dispute)
            <article class="account-row dispute-row" wire:key="against-{{ $dispute->id }}">
                <div class="account-identity">
                    <p class="account-name">#{{ $dispute->tagNormalized }}</p>
                    <p class="account-tag ui-help">Claimed by {{ '@'.($dispute->claimantName ?? 'unknown') }}</p>
                </div>
                <div class="account-status">
                    <x-ui.pill :tone="$statusTone($dispute->status)">{{ $dispute->status->label() }}</x-ui.pill>
                    @if($dispute->holderRespondsBy)
                        <p class="ui-help">Respond by {{ $dispute->holderRespondsBy->format('M j, Y') }}</p>
                    @endif
                </div>

                @if($respondingId === $dispute->id)
                    <form wire:submit="submitStatement" class="adm-form">
                        <x-ui.textarea id="holderNote-{{ $dispute->id }}" label="Your statement" wire:model="holderNote"
                            :error="$errors->first('holderNote')" rows="3" maxlength="2000"
                            hint="Explain why the tag is yours. At least 20 characters." />
                        <div class="settings-actions">
                            <x-ui.button type="submit" size="sm" wire:target="submitStatement" wire:loading.attr="disabled">Send statement</x-ui.button>
                            <x-ui.button type="button" variant="ghost" size="sm" wire:click="cancelRespond">Cancel</x-ui.button>
                        </div>
                    </form>
                @else
                    <div class="account-actions">
                        <x-ui.button type="button" size="sm" wire:click="startRespond({{ $dispute->id }})">Submit a statement</x-ui.button>
                        <x-ui.button type="button" variant="ghost" size="sm"
                            wire:click="release({{ $dispute->id }})"
                            wire:confirm="Release this tag to the claimant? This transfers ownership and cannot be undone.">Release to claimant</x-ui.button>
                    </div>
                @endif
            </article>
        @empty
            <x-ui.empty-state title="Nothing to respond to" body="No one is disputing an account you hold." />
        @endforelse
    </section>

    <section class="accounts-list" aria-labelledby="mine-heading">
        <h2 id="mine-heading">Disputes you filed</h2>

        @forelse($mine as $dispute)
            <article class="account-row dispute-row" wire:key="mine-{{ $dispute->id }}">
                <div class="account-identity">
                    <p class="account-name">#{{ $dispute->tagNormalized }}</p>
                    <p class="account-tag ui-help">Held by {{ '@'.($dispute->holderName ?? 'unknown') }} · filed {{ $dispute->createdAt?->format('M j, Y') }}</p>
                </div>
                <div class="account-status">
                    <x-ui.pill :tone="$statusTone($dispute->status)">{{ $dispute->status->label() }}</x-ui.pill>
                </div>

                @if($dispute->status->value === 'awaiting_claimant')
                    <x-ui.alert tone="info" title="A moderator asked for more information">{{ $dispute->decisionNote }}</x-ui.alert>
                    @if($infoId === $dispute->id)
                        <form wire:submit="submitInfo" class="adm-form">
                            <x-ui.textarea id="infoNote-{{ $dispute->id }}" label="Add the requested information" wire:model="infoNote"
                                :error="$errors->first('infoNote')" rows="3" maxlength="2000" hint="At least 20 characters." />
                            <div class="settings-actions">
                                <x-ui.button type="submit" size="sm" wire:target="submitInfo" wire:loading.attr="disabled">Send</x-ui.button>
                                <x-ui.button type="button" variant="ghost" size="sm" wire:click="cancelInfo">Cancel</x-ui.button>
                            </div>
                        </form>
                    @else
                        <div class="account-actions"><x-ui.button type="button" size="sm" wire:click="startInfo({{ $dispute->id }})">Add information</x-ui.button></div>
                    @endif
                @elseif($dispute->status->value === 'resolved_denied' && $dispute->decisionNote)
                    <p class="ui-help">Decision: {{ $dispute->decisionNote }}</p>
                @endif

                @if(in_array($dispute->status->value, ['open', 'awaiting_holder', 'awaiting_claimant'], true))
                    <div class="account-actions">
                        <x-ui.button type="button" variant="ghost" size="sm"
                            wire:click="confirmWithdraw({{ $dispute->id }})">Withdraw</x-ui.button>
                        @if($withdrawingId === $dispute->id)
                            <span class="dispute-confirm">
                                <x-ui.button type="button" variant="danger" size="sm" wire:click="withdraw">Confirm withdraw</x-ui.button>
                                <x-ui.button type="button" variant="ghost" size="sm" wire:click="cancelWithdraw">Keep it open</x-ui.button>
                            </span>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <x-ui.empty-state title="No disputes filed"
                body="If a tag you own is verified by someone else and you cannot get an in-game token, you can open a dispute from the Accounts page." />
        @endforelse
    </section>
</div>
