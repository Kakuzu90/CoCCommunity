<x-layouts.admin :title="'Dispute #'.$dispute->tagNormalized" heading="Review dispute">
    <section class="adm-panel adm-dispute">
        <a href="{{ route('admin.disputes.index') }}" class="adm-btn adm-btn-ghost">&larr; Back to queue</a>

        <dl class="adm-facts">
            <div><dt>Tag</dt><dd class="adm-mono">#{{ $dispute->tagNormalized }}</dd></div>
            <div><dt>Status</dt><dd>{{ $dispute->status->label() }}</dd></div>
            <div><dt>Claimant</dt><dd>{{ $dispute->claimantName ?? '–' }}</dd></div>
            <div><dt>Current holder</dt><dd>{{ $dispute->holderName ?? '–' }}</dd></div>
            <div><dt>Filed</dt><dd>{{ $dispute->createdAt?->format('Y-m-d H:i') }}</dd></div>
            <div><dt>Holder responds by</dt><dd>{{ $dispute->holderRespondsBy?->format('Y-m-d') ?? '–' }}</dd></div>
        </dl>

        <article class="adm-dispute-block">
            <h3>Claimant's case</h3>
            <p>{{ $dispute->reason }}</p>
            @if($dispute->evidence['notes'] !== '')
                <p class="adm-hint">Notes: {{ $dispute->evidence['notes'] }}</p>
            @endif
            @if(count($dispute->evidence['media']) > 0)
                <p class="adm-hint">{{ count($dispute->evidence['media']) }} private evidence image(s) attached. Access is audit-logged.</p>
            @endif
        </article>

        <article class="adm-dispute-block">
            <h3>Holder's response</h3>
            <p>{{ $dispute->holderResponse ?? 'No statement submitted yet.' }}</p>
        </article>

        @if($dispute->decisionNote)
            <article class="adm-dispute-block">
                <h3>Decision note</h3>
                <p>{{ $dispute->decisionNote }}</p>
            </article>
        @endif

        <div class="adm-callout">
            <p><strong>Decision bias.</strong> Absent decisive evidence, the current holder keeps the tag. A wrongful transfer is identity theft; a wrongly denied claimant can still verify later with a token.</p>
        </div>

        @if($dispute->status->isLive())
            @livewire('admin.resolve-dispute', ['disputeId' => $dispute->id], key('resolve-'.$dispute->id))
        @else
            <x-ui.alert tone="neutral" title="Already resolved">This dispute is {{ strtolower($dispute->status->label()) }} and cannot be changed.</x-ui.alert>
        @endif
    </section>
</x-layouts.admin>
