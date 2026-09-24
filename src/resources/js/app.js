import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import displayFont from '@fontsource/lilita-one/files/lilita-one-latin-400-normal.woff2?url';

// Shared by the gallery and future layouts; Livewire provides the single Alpine instance.
Alpine.data('uiSelect', () => ({
    ready: false, open: false, query: '', value: '', disabled: false, options: [], active: 0,
    observer: null, resetHandler: null,
    init() {
        this.sync();
        this.ready = true;
        this.observer = new MutationObserver(() => this.sync());
        this.observer.observe(this.$refs.native, { childList: true, subtree: true, attributes: true, attributeFilter: ['selected', 'disabled', 'value'] });
        this.resetHandler = () => setTimeout(() => this.sync(), 0);
        this.$refs.native.form?.addEventListener('reset', this.resetHandler);
    },
    sync() {
        this.value = this.$refs.native.value;
        this.disabled = this.$refs.native.disabled;
        this.options = [...this.$refs.native.options].filter(option => option.value && !option.disabled).map(option => ({ value: option.value, label: option.text }));
        if (this.disabled) this.close(false);
    },
    get selectedLabel() { return this.options.find(option => option.value === this.value)?.label || 'Choose an option'; },
    get filtered() { return this.options.filter(option => option.label.toLocaleLowerCase().includes(this.query.trim().toLocaleLowerCase())); },
    get activeId() { return this.open && this.filtered[this.active] ? this.$refs.list.id + '-' + this.active : null; },
    show() {
        if (this.disabled) return;
        this.query = '';
        this.active = Math.max(0, this.options.findIndex(option => option.value === this.value));
        this.open = true;
        this.$nextTick(() => { this.$refs.search.focus(); this.scroll(); });
    },
    close(restore) { this.open = false; if (restore) this.$refs.trigger.focus(); },
    choose(value) {
        this.$refs.native.value = value;
        this.sync();
        this.$refs.native.dispatchEvent(new Event('input', { bubbles: true }));
        this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
        this.close(true);
    },
    scroll() { this.$nextTick(() => document.getElementById(this.activeId)?.scrollIntoView({ block: 'nearest' })); },
    navigate(event) {
        if (!this.open) {
            if (['ArrowDown', 'ArrowUp'].includes(event.key)) { event.preventDefault(); this.show(); }
            return;
        }
        if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); this.close(true); }
        if (event.key === 'Tab') this.close(true);
        if (event.key === 'Enter') { event.preventDefault(); if (this.filtered[this.active]) this.choose(this.filtered[this.active].value); }
        if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const count = this.filtered.length;
            this.active = event.key === 'Home' ? 0 : event.key === 'End' ? count - 1 : count ? (this.active + (event.key === 'ArrowDown' ? 1 : -1) + count) % count : 0;
            this.scroll();
        }
    },
    destroy() { this.observer?.disconnect(); this.$refs.native.form?.removeEventListener('reset', this.resetHandler); },
}));

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
// Direct-to-storage avatar upload (specs/10 §3): the browser presigns an intent, PUTs the file
// straight to storage, polls the idempotent complete endpoint until the pipeline reports the media
// ready, then submits the attach form with its ULID. The app never proxies the bytes.
Alpine.data('avatarUploader', () => ({
    busy: false, statusText: '', error: '', ulid: '', preview: null,
    async pick(event) {
        const file = event.target.files[0];
        if (!file) return;
        this.error = '';
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { this.error = 'Choose a JPG, PNG or WebP image.'; this.reset(event); return; }
        if (file.size > 2 * 1024 * 1024) { this.error = 'That image is larger than 2 MB.'; this.reset(event); return; }
        this.busy = true; this.statusText = 'Uploading…';
        try {
            const ticket = await this.post('/uploads/intent', { collection: 'avatar', filename: file.name, size: file.size, mime: file.type });
            const put = await fetch(ticket.upload_url, { method: 'PUT', headers: ticket.headers, body: file });
            if (!put.ok) throw new Error('put');
            this.statusText = 'Processing…';
            const status = await this.awaitReady(ticket.media_ulid);
            if (status !== 'ready') { this.fail('That image could not be processed.'); return; }
            this.ulid = ticket.media_ulid;
            this.preview = URL.createObjectURL(file);
            this.statusText = 'Saving…';
            // Set the value imperatively: x-bind flushes on Alpine's next tick, which is after this
            // synchronous submit(), so relying on it would post an empty media_ulid.
            this.$refs.mediaInput.value = ticket.media_ulid;
            this.$refs.attachForm.submit();
        } catch (e) {
            this.fail('Upload failed. Please try again.');
        }
    },
    async awaitReady(ulid) {
        for (let i = 0; i < 30; i++) {
            const res = await fetch(`/uploads/${ulid}/complete`, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() } });
            if (res.ok) { const d = await res.json(); if (['ready', 'failed', 'quarantined'].includes(d.status)) return d.status; }
            await new Promise(r => setTimeout(r, 1000));
        }
        return 'timeout';
    },
    async post(url, body) {
        const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() }, body: JSON.stringify(body) });
        if (!res.ok) throw new Error('post');
        return res.json();
    },
    fail(message) { this.error = message; this.busy = false; this.statusText = ''; },
    reset(event) { if (event?.target) event.target.value = ''; },
    csrf() { return document.querySelector('meta[name=csrf-token]')?.content || ''; },
}));

// Free-text tag entry (profile languages): type a value and press Enter to add a removable chip.
// Each chip is mirrored to a hidden input so the form posts a plain `name[]` array.
Alpine.data('tagInput', ({ tags = [], max = 5, maxLength = 30 } = {}) => ({
    tags: Array.isArray(tags) ? tags.filter(t => typeof t === 'string') : [],
    draft: '', max, maxLength,
    add() {
        const value = this.draft.trim().slice(0, this.maxLength);
        if (!value) return;
        if (this.tags.length >= this.max) { this.draft = ''; return; }
        if (!this.tags.some(t => t.toLowerCase() === value.toLowerCase())) this.tags.push(value);
        this.draft = '';
    },
    remove(index) { this.tags.splice(index, 1); this.$nextTick(() => this.$refs.entry?.focus()); },
    onKey(event) {
        if (event.key === ',') { event.preventDefault(); this.add(); }
        if (event.key === 'Backspace' && this.draft === '' && this.tags.length) { event.preventDefault(); this.tags.pop(); }
    },
}));

// Vite includes this font in its manifest for the server-rendered preload.
void displayFont;
if (window.livewireScriptConfig) Livewire.start();
else Alpine.start();
