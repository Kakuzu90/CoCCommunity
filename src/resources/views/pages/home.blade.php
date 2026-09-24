<x-layouts.app>
    <section class="home-hero">
        <p class="home-eyebrow">The Clash of Clans community platform</p>
        <h1 class="home-title">Prove it's your base.</h1>
        <p class="home-lead">
            Verify in-game ownership, publish layouts people can actually trust, and find a clan
            that fits — without the guesswork.
        </p>
        <div class="home-cta">
            @guest
                <a href="{{ route('register') }}" class="ui-button" data-size="lg">Create your account</a>
                <a href="{{ route('bases.index') }}" class="ui-button" data-variant="secondary" data-size="lg">Browse bases</a>
            @else
                <a href="{{ route('bases.index') }}" class="ui-button" data-size="lg">Explore bases</a>
            @endguest
        </div>
    </section>

    <section class="home-values" aria-label="What you can do on Clash Commons">
        <x-ui.card>
            <h2 class="home-value-title">Verified ownership</h2>
            <p class="ui-help">Link your account with an in-game token, so a base or profile is provably yours — not just a claim.</p>
        </x-ui.card>
        <x-ui.card>
            <h2 class="home-value-title">Bases worth copying</h2>
            <p class="ui-help">Share layouts with real context and let the community surface what actually holds up.</p>
        </x-ui.card>
        <x-ui.card>
            <h2 class="home-value-title">Recruitment that fits</h2>
            <p class="ui-help">Clans and players find each other on verified activity and goals, not empty promises.</p>
        </x-ui.card>
    </section>
</x-layouts.app>
