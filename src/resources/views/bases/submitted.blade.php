<x-layouts.app title="Base submission" description="Check the status of your base layout.">
    <div class="base-page" x-data x-init="localStorage.removeItem('clashcommons-base-draft')">
        <header class="settings-head">
            <p class="ui-eyebrow">Base submission</p>
            <h1>{{ $base->title }}</h1>
        </header>
        <section class="settings-card" aria-live="polite">
            @if($base->status->value === 'published')
                <h2 class="settings-card-title">Published</h2>
                <p class="ui-help">Your base is ready. The public layout page and feed arrive with the next Phase 3 task.</p>
            @else
                <h2 class="settings-card-title">Processing uploads</h2>
                <p class="ui-help">Your base will publish when every attached upload is ready. Refresh this page to check again.</p>
                <a class="ui-button" data-variant="secondary" data-size="md" href="{{ route('bases.submitted', $base->ulid) }}">Check status</a>
            @endif
            <a href="{{ route('bases.index') }}">Back to bases</a>
        </section>
    </div>
</x-layouts.app>
