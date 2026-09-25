<div class="adm-resolve">
    @if($resolved)
        <x-ui.alert tone="success">{{ $flash }} <a href="{{ route('admin.disputes.index') }}">Back to the queue.</a></x-ui.alert>
    @else
        <h3>Make a decision</h3>
        <p class="adm-hint">Every decision is recorded to the moderation log with your note. You cannot decide a dispute you are a party to.</p>

        <x-ui.textarea id="resolve-note" label="Decision note (required)" wire:model="note"
            :error="$errors->first('note')" rows="3" maxlength="2000"
            hint="Explain the reasoning. Stored on the immutable moderation record; the parties see a short version." />

        <div class="settings-actions adm-resolve-actions">
            <x-ui.button type="button" wire:click="transfer"
                wire:confirm="Transfer this tag to the claimant? This moves verified ownership and is audited.">Transfer to claimant</x-ui.button>
            <x-ui.button type="button" variant="ghost" wire:click="deny">Deny (holder keeps it)</x-ui.button>
            <x-ui.button type="button" variant="ghost" wire:click="requestInfo">Request more info</x-ui.button>
            <x-ui.button type="button" variant="danger" wire:click="suspend"
                wire:confirm="Suspend this tag? Neither party will hold it.">Suspend tag</x-ui.button>
        </div>
    @endif
</div>
