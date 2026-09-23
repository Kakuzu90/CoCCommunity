---
name: ux
description: Interaction and UX quality for this app's screens and flows — feedback, states, hierarchy, forms, perceived performance, and mobile ergonomics. Use when building or reviewing any user-facing flow (linking/verifying accounts, uploads, recruitment, etc.), or when a screen "feels off". Complements the livewire-ui skill (which covers visual/component conventions).
---

# UX

Good UX here means the user always knows **what state they're in, what just happened, and what to do next** — without refreshing the page. Apply this to every flow; run the checklist before calling a screen done.

## Principles

1. **Never tell the user to refresh.** Anything asynchronous (queued jobs: verification, media processing, sync) must update itself via `wire:poll` or events. "Refresh to see the result" is a bug, not a message.
2. **Immediate acknowledgement.** Every action confirms within ~100ms: `wire:loading` disables the button and shows progress; the affected row enters a visible in-progress state. Never let a click look ignored.
3. **Feedback near the action.** Success/error appears on or beside the thing acted on — not only as a page-top banner. Field errors sit under the field (from a `FormRequest`/rules), not in a global alert.
4. **Design the four states, every time.** Loading, empty, error, populated. The empty state names the next action; the error state is recoverable and specific.
5. **Optimistic where safe, honest where not.** Reflect a change instantly when it can't fail; when it can (external API), show a pending state and resolve it, including the failure path.
6. **Hierarchy guides the eye.** One primary action per view (violet CTA); everything else secondary/ghost. Lead with the summary, then detail. Don't make everything a card of equal weight.
7. **Respect the async reality.** Queued work needs a running worker; a flow that depends on the queue must degrade gracefully (timeout message) if it stalls, and the dev setup must run the worker.
8. **Mobile first, thumb first.** Tap targets ≥44px, primary action reachable, no horizontal scroll, inputs use the right `inputmode`/`autocomplete`.

## Async flow pattern (verification, uploads, sync)

The canonical shape for "user triggers queued work, then sees the result live":

1. On submit: `wire:loading` on the button; dispatch the job; mark the item **in-progress** in component state (e.g. `$verifying[$id] = now()`).
2. Render an in-progress row (spinner + "Verifying ownership…"), replacing the input.
3. Conditionally poll only while something is in progress:
   `@if ($verifying) <div wire:poll.1500ms="tick"></div> @endif`
4. In `tick()`: re-read state. Resolve each item to **success** (state changed → confirm inline, drop from in-progress) or **failure** (a failure signal/notification since the attempt → show a specific, recoverable error) or **timeout** (past N seconds → "still working, try again").
5. Stop polling when nothing is in progress (the `@if` disappears), so idle pages aren't hammering the server.

Never poll unconditionally on a page at rest.

## Forms

- Validation in a `FormRequest`/`rules()` (see `livewire-ui`); errors render under the field.
- Disable submit + show progress via `wire:loading.attr="disabled"` and `wire:target`.
- Preserve input on error; never clear a form the user must re-type.
- Placeholders show format (`#2P0YQRL8V`), not instructions; instructions go in helper text.
- Debounce live inputs; confirm destructive actions inline (no native `confirm()`).

## Feedback surfaces

- **Inline row state** for per-item results (an account verifying/verified/failed).
- **Toast / flash** for global one-off confirmations ("Account verified").
- **Banner** only for page-level, persistent context (e.g. "email not verified").
- Match severity to color tokens: `verified` = success, `alert` = error, `accent`/`warning` = caution. Never a red wall for a routine outcome.

## Review checklist (run before shipping a screen)

- [ ] No "refresh to see" anywhere; async results resolve live.
- [ ] Every action acknowledges in <100ms (loading/disabled state).
- [ ] Loading, empty, error, populated states all present.
- [ ] Errors are specific, recoverable, and near the cause.
- [ ] One clear primary action; hierarchy reads at a glance.
- [ ] Async paths handle success, failure, **and** timeout/stall.
- [ ] Works at 375px: targets ≥44px, no horizontal scroll, correct keyboards.
- [ ] Nothing depends on a worker that isn't running in dev (or it degrades clearly).
- [ ] Copy is active and specific ("Verify ownership", then "Verified").

## CoC-specific flows

- **Link → verify:** after linking, the account row invites token entry; on submit it shows *verifying*, then flips to *verified* (or a clear failure) live. Show `last_synced_at` once synced.
- **Uploads:** signed-URL upload shows progress; the item stays *processing* until `ready`, then reveals the thumbnail; `rejected` explains why.
- **Stale data:** always label snapshot age; never present cached data as live.
