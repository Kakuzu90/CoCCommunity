import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import displayFont from '@fontsource/lilita-one/files/lilita-one-latin-400-normal.woff2?url';

// Shared by the gallery and future layouts; Livewire provides the single Alpine instance.
Alpine.data('uiModal', () => ({
    previous: null, timer: null,
    show() {
        if (this.$el.open) return;
        this.previous = document.activeElement;
        this.$el.showModal();
        this.$nextTick(() => (this.$el.querySelector('[autofocus]') || this.$el.querySelector('input, button'))?.focus());
    },
    close() {
        if (!this.$el.open || this.timer) return;
        this.$el.classList.add('is-closing');
        const duration = matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : parseFloat(getComputedStyle(this.$el).getPropertyValue('--dur-base'));
        this.timer = setTimeout(() => {
            this.$el.close();
            this.$el.classList.remove('is-closing');
            this.previous?.focus();
            this.timer = null;
        }, duration);
    },
    trap(event) {
        const items = [...this.$el.querySelectorAll('button, input, select, textarea, a[href], [tabindex]')]
            .filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length);
        if (!items.length) { event.preventDefault(); this.$el.focus(); return; }
        event.preventDefault();
        const index = items.indexOf(document.activeElement);
        items[(index + (event.shiftKey ? -1 : 1) + items.length) % items.length].focus();
    },
    backdrop(event) {
        const box = this.$el.getBoundingClientRect();
        if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) this.close();
    },
    destroy() { clearTimeout(this.timer); },
}));
Alpine.data('uiTooltip', () => ({
    visible: false,
    init() {
        this.$watch('visible', visible => {
            if (!visible) return;
            this.$nextTick(() => requestAnimationFrame(() => {
                const tip = this.$refs.tip;
                tip.style.translate = '0px 0px';
                const box = tip.getBoundingClientRect();
                const inset = parseFloat(getComputedStyle(tip).getPropertyValue('--space-2'));
                const offset = box.left < inset ? inset - box.left : Math.min(0, innerWidth - inset - box.right);
                tip.style.translate = `${offset}px 0px`;
            }));
        });
    },
}));
Alpine.data('uiDropdown' , () => ({
    open: false,
    items() { return [...this.$refs.menu.querySelectorAll('[role=menuitem]:not(:disabled)')]; },
    show() { this.open = true; this.$nextTick(() => requestAnimationFrame(() => this.items()[0]?.focus())); },
    hide(restore) { this.open = false; if (restore) this.$refs.trigger.focus(); },
    toggle() { this.open ? this.hide(true) : this.show(); },
    navigate(event) {
        const items = this.items();
        if (event.key === 'Tab') { this.open = false; return; }
        if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        let index = items.indexOf(document.activeElement);
        index = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1 : (index + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
        items[index]?.focus();
    },
}));
Alpine.data('uiTabs', () => ({
    active: 0,
    navigate(event) {
        const tabs = [...event.currentTarget.querySelectorAll('[role=tab]')];
        if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        this.active = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (this.active + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
        tabs[this.active]?.focus();
    },
}));
Alpine.data('uiSentinel', () => ({
    observer: null,
    init() {
        this.observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting)) this.$dispatch('load-more');
        });
        this.observer.observe(this.$el);
    },
    destroy() { this.observer?.disconnect(); },
}));
// Vite includes this font in its manifest for the server-rendered preload.
void displayFont;
if (window.livewireScriptConfig) Livewire.start();
else Alpine.start();
