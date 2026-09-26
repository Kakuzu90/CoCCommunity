import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import displayFont from '@fontsource/lilita-one/files/lilita-one-latin-400-normal.woff2?url';
import { unitFire } from './unit-fire';

Alpine.data('unitFire', unitFire);

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
    get selectedLabel() { return this.options.find(option => option.value === this.value)?.label || this.$refs.native.querySelector('option[value=""]')?.text || 'Choose an option'; },
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

// Methods run from child elements (the close button), where $el is that child, so they use $root.
Alpine.data('uiModal', () => ({
    previous: null, timer: null,
    show() {
        if (this.$root.open) return;
        this.previous = document.activeElement;
        this.$root.showModal();
        this.$nextTick(() => (this.$root.querySelector('[autofocus]') || this.$root.querySelector('input, button'))?.focus());
    },
    close() {
        if (!this.$root.open || this.timer) return;
        this.$root.classList.add('is-closing');
        const duration = matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : parseFloat(getComputedStyle(this.$root).getPropertyValue('--dur-base'));
        this.timer = setTimeout(() => {
            this.$root.close();
            this.$root.classList.remove('is-closing');
            this.previous?.focus();
            this.timer = null;
        }, duration);
    },
    trap(event) {
        const items = [...this.$root.querySelectorAll('button, input, select, textarea, a[href], [tabindex]')]
            .filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length);
        if (!items.length) { event.preventDefault(); this.$root.focus(); return; }
        event.preventDefault();
        const index = items.indexOf(document.activeElement);
        items[(index + (event.shiftKey ? -1 : 1) + items.length) % items.length].focus();
    },
    backdrop(event) {
        const box = this.$root.getBoundingClientRect();
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
// Linkable tabs mirror the open panel in the URL hash (#<id>-panel-<key>) so a tab can be shared.
Alpine.data('uiTabs', ({ linkable = false } = {}) => ({
    active: 0,
    init() {
        if (!linkable) return;
        const panels = [...this.$el.querySelectorAll(':scope > [role=tabpanel]')];
        const fromHash = panels.findIndex(panel => '#' + panel.id === location.hash);
        if (fromHash > 0) this.active = fromHash;
        this.$watch('active', index => {
            if (panels[index]) history.replaceState(null, '', '#' + panels[index].id);
        });
    },
    navigate(event) {
        const tabs = [...event.currentTarget.querySelectorAll('[role=tab]')];
        if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        this.active = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (this.active + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
        tabs[this.active]?.focus();
    },
}));
// StatBlock count-up (specs/18 §4): runs once when the figure scrolls into view, and never under
// reduced motion. The real value is already in the markup, so without JS nothing is lost.
Alpine.data('uiCountUp', (target) => ({
    observer: null,
    init() {
        if (target <= 0 || matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
        const duration = parseFloat(getComputedStyle(this.$el).getPropertyValue('--dur-count')) || 0;
        if (duration <= 0) return;
        const format = new Intl.NumberFormat('en-US');
        this.$el.textContent = '0';
        this.observer = new IntersectionObserver(entries => {
            if (!entries.some(entry => entry.isIntersecting)) return;
            this.observer.disconnect();
            const start = performance.now();
            const step = now => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                this.$el.textContent = format.format(Math.round(target * eased));
                if (progress < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        });
        this.observer.observe(this.$el);
    },
    destroy() { this.observer?.disconnect(); },
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
const directImageUploader = (collection, maxSizeMb) => ({
    busy: false, statusText: '', error: '', ulid: '', preview: null,
    async pick(event) {
        const file = event.target.files[0];
        if (!file) return;
        this.error = '';
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { this.error = 'Choose a JPG, PNG or WebP image.'; this.reset(event); return; }
        if (file.size > maxSizeMb * 1024 * 1024) { this.error = `That image is larger than ${maxSizeMb} MB.`; this.reset(event); return; }
        this.busy = true; this.statusText = 'Uploading…';
        try {
            const ticket = await this.post('/uploads/intent', { collection, filename: file.name, size: file.size, mime: file.type });
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
});
Alpine.data('avatarUploader', () => directImageUploader('avatar', 2));
Alpine.data('accountImageUploader', () => directImageUploader('account_image', 5));

Alpine.data('baseComposer', () => ({
    items: [], linkFeedback: '',
    get pendingCount() { return this.items.filter(item => ['uploading', 'processing'].includes(item.status)).length; },
    get failedCount() { return this.items.filter(item => item.status === 'failed').length; },
    init() {
        const old = this.$el.dataset.oldScreenshots.split(',').filter(Boolean);
        const draft = this.$el.dataset.hasErrors === '1' ? null : this.readDraft();
        const ids = old.length ? old : (draft?.screenshots || []);
        this.items = ids.map((ulid, index) => ({ key: ulid, name: `Saved screenshot ${index + 1}`, file: null, status: 'ready', progress: 100, error: '', ulid }));
        if (draft && this.$el.dataset.hasErrors !== '1') {
            for (const [name, value] of Object.entries(draft.fields || {})) {
                const controls = this.$el.querySelectorAll(`[name="${name}"]`);
                for (const control of controls) {
                    if (control.type === 'radio') control.checked = control.value === value;
                    else control.value = value;
                }
            }
        }
        this.checkLink(this.$el.querySelector('[name="base_link"]')?.value || '');
    },
    readDraft() {
        try { return JSON.parse(localStorage.getItem('clashcommons-base-draft') || 'null'); }
        catch { return null; }
    },
    saveDraft() {
        const fields = {};
        for (const control of this.$el.querySelectorAll('input[name], textarea[name], select[name]')) {
            if (['hidden', 'file'].includes(control.type) || (control.type === 'radio' && !control.checked)) continue;
            fields[control.name] = control.value;
        }
        try { localStorage.setItem('clashcommons-base-draft', JSON.stringify({ fields, screenshots: this.items.filter(item => item.status === 'ready').map(item => item.ulid) })); }
        catch { /* Private browsing may block local storage; publishing still works. */ }
    },
    checkLink(value) {
        if (!value.trim()) { this.linkFeedback = ''; return; }
        try {
            const url = new URL(value);
            this.linkFeedback = url.protocol === 'https:' && url.hostname === 'link.clashofclans.com'
                && url.searchParams.get('action') === 'OpenLayout' && !!url.searchParams.get('id')
                ? 'OpenLayout link detected. The layout ID will be checked when you publish.'
                : 'Use a base link copied from Clash of Clans.';
        } catch { this.linkFeedback = 'Use a base link copied from Clash of Clans.'; }
    },
    addTag(tag) {
        const field = this.$el.querySelector('[name="tags_text"]');
        const tags = field.value.split(',').map(value => value.trim()).filter(Boolean);
        if (!tags.includes(tag) && tags.length < 10) tags.push(tag);
        field.value = tags.join(', ');
        this.saveDraft();
    },
    addFiles(files) {
        for (const file of Array.from(files)) {
            if (this.items.length >= 2) break;
            const item = { key: crypto.randomUUID(), name: file.name, file, status: 'uploading', progress: 0, error: '', ulid: '' };
            this.items.push(item);
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
                item.status = 'failed'; item.error = 'Choose a JPG, PNG or WebP image up to 5 MB.'; item.file = null;
            } else this.upload(item);
        }
    },
    async upload(item) {
        item.status = 'uploading'; item.error = ''; item.progress = 0;
        try {
            const ticket = await this.post('/uploads/intent', { collection: 'base_screenshot', filename: item.file.name, size: item.file.size, mime: item.file.type });
            await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('PUT', ticket.upload_url);
                for (const [name, value] of Object.entries(ticket.headers)) xhr.setRequestHeader(name, value);
                xhr.upload.onprogress = event => { if (event.lengthComputable) item.progress = Math.round(event.loaded / event.total * 100); };
                xhr.onload = () => xhr.status >= 200 && xhr.status < 300 ? resolve() : reject(new Error('Upload failed'));
                xhr.onerror = () => reject(new Error('Upload failed'));
                xhr.send(item.file);
            });
            item.status = 'processing';
            for (let attempt = 0; attempt < 60; attempt++) {
                const result = await this.post(`/uploads/${ticket.media_ulid}/complete`, {});
                if (result.status === 'ready') {
                    item.status = 'ready'; item.ulid = ticket.media_ulid; item.file = null; this.saveDraft(); return;
                }
                if (['failed', 'quarantined'].includes(result.status)) throw new Error('That image could not be processed.');
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
            throw new Error('Processing is taking longer than expected. Retry the upload.');
        } catch (error) {
            item.status = 'failed'; item.error = error.message || 'Upload failed. Please try again.';
        }
    },
    retry(item) { if (item.file) this.upload(item); },
    remove(item) { this.items = this.items.filter(candidate => candidate.key !== item.key); this.saveDraft(); },
    async post(url, body) {
        const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }, body: JSON.stringify(body) });
        if (!response.ok) throw new Error('Upload failed. Please try again.');
        return response.json();
    },
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
