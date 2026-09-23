<button type="button" aria-label="Toggle colour theme"
    x-data="{
        dark: (window.__theme?.get() ? window.__theme.get() === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches)
    }"
    x-on:theme-changed.window="dark = $event.detail === 'dark'"
    @click="window.__theme.toggle()"
    class="grid place-items-center w-9 h-9 rounded-xl text-content-muted hover:text-content hover:bg-surface-2 focus:outline-none focus:ring-2 focus:ring-primary transition">
    <svg x-show="!dark" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
    </svg>
    <svg x-show="dark" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/>
    </svg>
</button>
