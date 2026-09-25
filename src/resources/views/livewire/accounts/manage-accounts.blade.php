@php
    $statusTone = fn ($status) => match ($status->value) {
        'verified' => 'primary',
        'disputed' => 'warning',
        'suspended' => 'danger',
        default => 'neutral',
    };
@endphp
<div class="accounts-page">
    <header class="settings-head">
        <p class="ui-eyebrow">Accounts</p>
        <h1>Your Clash of Clans accounts</h1>
        <p class="ui-help">Attach a player tag, confirm it is you, then verify ownership with an in-game token. Verifying earns your badge and unlocks publishing.</p>
    </header>

    @if($flash !== '')
        <x-ui.alert tone="success" wire:key="accounts-flash">{{ $flash }}</x-ui.alert>
    @endif

    <section class="settings-card accounts-attach" aria-labelledby="attach-heading">
        @if($preview === null)
            <form wire:submit="lookup" class="adm-form accounts-form">
                <div>
                    <h2 id="attach-heading">Add an account</h2>
                    <p class="ui-help">Your player tag is in the game under your name, for example <span class="account-tag">#2PP0LJQ</span>.</p>
                </div>
                <x-ui.input id="tag" label="Player tag" wire:model="tag" prefix="#"
                    :error="$errors->first('tag')"
                    hint="Letters and numbers only. The leading # is optional."
                    autocomplete="off" autocapitalize="characters" spellcheck="false" />
                <div class="settings-actions">
                    <x-ui.button type="submit" wire:target="lookup" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="lookup">Look up account</span>
                        <span wire:loading wire:target="lookup">Looking up…</span>
                    </x-ui.button>
                </div>
            </form>
        @else
            <div class="accounts-confirm" wire:key="confirm-{{ $preview['tag'] }}">
                <h2 id="attach-heading">Is this you?</h2>

                <x-ui.card class="account-preview">
                    <p class="account-name">{{ $preview['ign'] }}</p>
                    <p class="account-tag ui-help">{{ $preview['tag'] }}</p>
                    <dl class="account-facts">
                        <div><dt>Town Hall</dt><dd>{{ $preview['thLevel'] }}</dd></div>
                        <div><dt>Trophies</dt><dd>{{ number_format($preview['trophies']) }}</dd></div>
                        @if($preview['leagueName'])<div><dt>League</dt><dd>{{ $preview['leagueName'] }}</dd></div>@endif
                        @if($preview['clanName'])<div><dt>Clan</dt><dd>{{ $preview['clanName'] }}</dd></div>@endif
                    </dl>
                    @if($preview['stale'])
                        <p class="ui-help account-stale">Showing recently saved data while the game API catches up.</p>
                    @endif
                </x-ui.card>

                @if($preview['conflictHolder'])
                    <x-ui.alert tone="warning" title="This account is already verified">
                        {{ $preview['tag'] }} is currently verified by {{ '@'.$preview['conflictHolder'] }}. If it is yours,
                        verifying with your in-game token transfers it to you right away. That is the fastest path. If you
                        cannot get the token (you lost the device, or recovered the account through Supercell), you can open
                        an ownership dispute for a moderator to review.
                    </x-ui.alert>

                    @if($openingDispute)
                        <form wire:submit="openDispute" class="adm-form accounts-form accounts-dispute" wire:key="dispute-form">
                            <div>
                                <h3>Open an ownership dispute</h3>
                                <p class="ui-help">Disputes are slow and evidence-based. Absent decisive proof the current holder keeps the tag, so explain clearly why this account is yours.</p>
                            </div>
                            <x-ui.textarea id="disputeReason" label="Why is this account yours?" wire:model="disputeReason"
                                :error="$errors->first('disputeReason')" rows="4" maxlength="{{ (int) config('coc.dispute.reason_max') }}"
                                hint="At least 20 characters. Describe changes only the owner would know, such as recent name or clan changes." />
                            <x-ui.textarea id="disputeNotes" label="Anything else (optional)" wire:model="disputeNotes"
                                :error="$errors->first('disputeNotes')" rows="2" maxlength="2000"
                                hint="Do not paste real-world ID documents. Use an in-game token or in-game screenshots." />
                            <div class="settings-actions">
                                <x-ui.button type="submit" wire:target="openDispute" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="openDispute">File dispute</span>
                                    <span wire:loading wire:target="openDispute">Filing…</span>
                                </x-ui.button>
                                <x-ui.button type="button" variant="ghost" wire:click="$set('openingDispute', false)">Cancel</x-ui.button>
                            </div>
                        </form>
                    @else
                        <div class="settings-actions">
                            <x-ui.button type="button" variant="ghost" wire:click="startDispute">I can't get the token — open a dispute</x-ui.button>
                        </div>
                    @endif
                @endif

                <form wire:submit="verify" class="adm-form accounts-form">
                    <ol class="accounts-steps">
                        <li>In Clash of Clans, open <strong>Settings</strong>.</li>
                        <li>Choose <strong>More Settings</strong>, then <strong>API Token</strong>.</li>
                        <li>Tap <strong>Copy</strong> and paste the token below.</li>
                    </ol>
                    <x-ui.input id="token" label="In-game API token" wire:model="token"
                        :error="$errors->first('token')"
                        hint="Tokens expire after a few minutes. Copy a fresh one right before you paste."
                        autocomplete="off" spellcheck="false" />
                    <div class="settings-actions">
                        <x-ui.button type="submit" wire:target="verify" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="verify">Verify ownership</span>
                            <span wire:loading wire:target="verify">Verifying…</span>
                        </x-ui.button>
                        <x-ui.button type="button" variant="ghost" wire:click="cancel">Use a different tag</x-ui.button>
                    </div>
                </form>
            </div>
        @endif
    </section>

    <section class="accounts-list" aria-labelledby="list-heading">
        <h2 id="list-heading">Attached accounts</h2>

        @forelse($accounts as $account)
            <article class="account-row" wire:key="account-{{ $account->id }}">
                <div class="account-identity">
                    <p class="account-name">{{ $account->ign }}</p>
                    <p class="account-tag ui-help">{{ $account->tag }}</p>
                </div>

                <p class="account-meta ui-help">
                    Town Hall {{ $account->thLevel }} · {{ number_format($account->trophies) }} trophies{{ $account->leagueName ? ' · '.$account->leagueName : '' }}
                </p>

                <div class="account-status">
                    @if($account->isFeatured)<x-ui.badge variant="featured">Featured</x-ui.badge>@endif
                    <x-ui.pill :tone="$statusTone($account->status)">{{ $account->status->label() }}</x-ui.pill>
                </div>

                <div class="account-actions">
                    @if($confirmingDetachId === $account->id)
                        <form wire:submit="detach" class="account-detach">
                            <x-ui.input id="detach-password-{{ $account->id }}" type="password"
                                label="Confirm your password to release this account" wire:model="password"
                                :error="$errors->first('password')" autocomplete="current-password" />
                            <div class="settings-actions">
                                <x-ui.button type="submit" variant="danger" size="sm"
                                    wire:target="detach" wire:loading.attr="disabled">Release account</x-ui.button>
                                <x-ui.button type="button" variant="ghost" size="sm" wire:click="cancelDetach">Keep it</x-ui.button>
                            </div>
                        </form>
                    @else
                        <x-ui.button type="button" variant="ghost" size="sm"
                            wire:click="confirmDetach({{ $account->id }})">Detach</x-ui.button>
                    @endif
                </div>
            </article>
        @empty
            <x-ui.empty-state title="No accounts attached yet"
                body="Add your Clash of Clans account above and verify it with an in-game token to earn your verified badge." />
        @endforelse
    </section>
</div>
