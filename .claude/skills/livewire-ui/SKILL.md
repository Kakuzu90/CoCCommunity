---
name: livewire-ui
description: Build UI in this app with Livewire + Blade + Tailwind. Use when creating or changing pages, components, forms, lists, or modals. Enforces mobile-first layout, thin components, accessible markup, and full loading/empty/error states.
---

# Livewire UI

Frontend is server-rendered Livewire + Blade, styled with Tailwind. Most CoC players are on phones — **mobile-first is not optional** (see [`specs/01-product-and-requirements.md`](../../../specs/01-product-and-requirements.md) NFRs).

**Colour, type, and tokens come from [`specs/12-design-system.md`](../../../specs/12-design-system.md) — read it before writing any markup.** Direction is **Dark Elixir Royal**: primary = Royal violet (`--primary`), accent = Gold (`--accent`), on a deep violet-black ground in dark mode. Type is **Baloo 2** (display) + **Rubik** (body/data). Never hardcode a hex that duplicates a token.

## Component rules

- **Thin components, like controllers.** A Livewire component authorizes (Policy), holds view state, and delegates writes to a Service/Action. No business logic in the component. See the `laravel-module` skill.
- **Validation in a `FormRequest`-equivalent only:** a form object or `rules()`/`#[Validate]` definition — never `$this->validate()` with rules written inline in the action method body.
- **Never trust component state for authorization.** Re-check the Policy on every action; a hidden button is not access control.
- One component = one responsibility. A base card, a base list, a filter bar are separate components, not one god component.
- Public/read pages that don't need reactivity stay plain Blade — don't reach for Livewire when a static view works.

## Layout & styling

- **Mobile-first:** base styles target small screens; add `sm:`/`md:`/`lg:` upward. Never desktop-first with overrides down.
- Use Tailwind utilities and existing shared Blade components. **Do not invent one-off CSS files or inline `<style>`** when a utility or shared component exists.
- Extract a repeated markup block into a Blade component (`<x-base-card>`) once it appears a second time — don't copy-paste markup.
- Use the design tokens from [`specs/12-design-system.md`](../../../specs/12-design-system.md), exposed via `tailwind.config.js`. Prefer semantic utilities (`bg-primary`, `text-muted`, `border-border`) over raw colours. Don't hardcode hex that duplicates a token.
- **Primary CTA = Royal violet; Gold is a highlight, not a second CTA colour.** Semantic colours (`--verified`, `--alert`, `--warning`) are for state only, never as accents.
- Keep tap targets ≥ 44px, readable contrast, and no horizontal scroll on a phone.

## Every list/data view needs four states

Do not ship a view that only handles the happy path:
1. **Loading** — `wire:loading` skeleton or spinner, not a blank flash.
2. **Empty** — a real empty state with a next action ("No bases yet — share one"), not a bare "No results".
3. **Error** — a recoverable message, never a white screen or a raw exception.
4. **Populated** — paginated (`WithPagination`); never dump an unbounded list.

## Forms

- Show inline field errors from validation; disable submit + show progress via `wire:loading.attr="disabled"` / `wire:target`.
- Debounce live inputs (`wire:model.live.debounce.300ms`) — don't fire a round-trip per keystroke.
- Confirm destructive actions (delete base, unlink account, ban) before running them.

## Media in the UI

- Render images from the CDN/signed URLs the `media` records provide (see [`specs/05-media-storage.md`](../../../specs/05-media-storage.md)). Never build storage paths by hand in Blade.
- Always set `alt`, `width`/`height` (avoid layout shift), and `loading="lazy"` on non-critical images.
- Uploads go through the signed-URL flow — the component requests the URL and shows progress; it does not stream file bytes through Livewire.

## Stale data

- Where a view shows CoC snapshot data, surface `last_synced_at` ("updated 3h ago") so users know it isn't live. Never present a snapshot as real-time.

## Accessibility (baseline, not optional)

- Semantic elements (`<button>` for actions, `<a>` for navigation — not clickable `<div>`s).
- Labels tied to inputs; `aria-*` only where semantics aren't enough.
- Visible focus states; keyboard-operable modals/menus (Esc to close, focus trap).

## Escaping

- Blade auto-escapes — rely on it. `{!! !!}` is banned on any user-supplied content. Sanitize server-side before rendering any rich text.

## Anti-slop (markup)

- No dead markup: no commented-out blocks, unused `<x-*>` imports, placeholder "Lorem ipsum", or example data left in a component.
- No narration comments in Blade (`{{-- loop over bases --}}`). Comment only non-obvious intent.
- Match the existing components' structure and class ordering before writing new ones — read a neighbor first.
- Don't duplicate a component that already exists; reuse or extend it.

## Before done

- Check the view at phone width and desktop.
- All four list states render.
- Pest/Livewire test for the component's key interaction + the authorization-denied path.
- Report what you actually verified.
