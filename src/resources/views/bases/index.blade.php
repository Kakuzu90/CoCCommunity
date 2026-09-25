<x-layouts.app title="Bases" description="Share your Clash of Clans base layouts.">
    <div class="base-page">
        <header class="settings-head">
            <p class="ui-eyebrow">Bases</p>
            <h1>Base layouts</h1>
            <p class="ui-help">The layout feed is being built. You can publish a base now and check its processing status.</p>
        </header>
        <section class="settings-card">
            <h2 class="settings-card-title">Share a layout</h2>
            <p class="ui-help">Add an official base link, Town Hall level, and the details players need to find it.</p>
            @php($guard = app(\App\Domain\Auth\Services\AccountGuard::class))
            @if($guard->hasVerifiedEmail(auth()->user()) && $guard->canWrite(auth()->user()) && $guard->hasVerifiedCocAccount(auth()->user()))
                <a class="ui-button" data-variant="primary" data-size="md" href="{{ route('bases.create') }}">Publish a base</a>
            @else
                <p class="ui-help">Verify an in-game account to publish.</p>
                <a class="ui-button" data-variant="secondary" data-size="md" href="{{ route('accounts.index') }}">Manage accounts</a>
            @endif
            <a href="{{ route('home') }}">Back to home</a>
        </section>
    </div>
</x-layouts.app>
