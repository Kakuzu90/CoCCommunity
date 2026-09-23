<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8">

        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-primary">Dashboard</p>
                <h1 class="mt-1 font-display font-extrabold text-2xl sm:text-3xl text-content tracking-tight">
                    Welcome back, {{ auth()->user()->name }}
                </h1>
                <p class="mt-1 text-sm text-content-muted">Manage your Clash of Clans accounts, bases, and clan life in one place.</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('accounts.index') }}" wire:navigate
                class="group rounded-2xl bg-surface border border-line p-5 shadow-sm hover:border-line-strong hover:shadow-md transition">
                <div class="grid place-items-center w-11 h-11 rounded-xl bg-primary-soft text-primary">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V5z"/><path d="M9 12l2 2 4-4"/></svg>
                </div>
                <h3 class="mt-4 font-display font-bold text-content">Link an account</h3>
                <p class="mt-1 text-sm text-content-muted">Add a CoC account and verify ownership with your in-game token.</p>
                <span class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primary group-hover:gap-2 transition-all">Go to accounts →</span>
            </a>

            <div class="rounded-2xl bg-surface border border-line p-5 shadow-sm opacity-80">
                <div class="grid place-items-center w-11 h-11 rounded-xl bg-accent-soft text-accent">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                </div>
                <h3 class="mt-4 font-display font-bold text-content">Share a base</h3>
                <p class="mt-1 text-sm text-content-muted">Publish war and farming layouts with screenshots.</p>
                <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-content-faint">Coming soon</span>
            </div>

            <div class="rounded-2xl bg-surface border border-line p-5 shadow-sm opacity-80">
                <div class="grid place-items-center w-11 h-11 rounded-xl bg-verified-soft text-verified">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/></svg>
                </div>
                <h3 class="mt-4 font-display font-bold text-content">Find a clan</h3>
                <p class="mt-1 text-sm text-content-muted">Browse recruitment posts or list yourself as looking for a clan.</p>
                <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-content-faint">Coming soon</span>
            </div>
        </div>

    </div>
</x-app-layout>
