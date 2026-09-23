# CLAUDE.md

Guidance for Claude Code in this repo.

## Read this first

The full engineering rules live in [`AGENTS.md`](AGENTS.md) — read it before writing code. It covers architecture, conventions, the non-negotiables (no Redis, no media in DB, no CoC API in request paths, policy-first authz, no marketplace payments), testing, security, and the anti-slop rules. Everything there applies to you.

The product/technical design lives in [`specs/`](specs/); load order is in [`specs/README.md`](specs/README.md).

## How to load context efficiently

Don't read every spec for every task. Load `specs/README.md` + the spec for the module you're touching + its listed dependencies:

- Building a module → [`specs/02-architecture-and-stack.md`](specs/02-architecture-and-stack.md) + [`specs/03-database.md`](specs/03-database.md)
- CoC accounts, verification, sync → [`specs/04-coc-integration-and-claiming.md`](specs/04-coc-integration-and-claiming.md)
- Uploads → [`specs/05-media-storage.md`](specs/05-media-storage.md)
- Anything user-facing → [`specs/06-auth-and-security.md`](specs/06-auth-and-security.md)

## Project skills

Invoke these when the task matches; each encodes decisions from the specs so you don't re-derive them:

- **`laravel-module`** — creating or extending a bounded module (structure, ownership, events).
- **`coc-integration`** — anything touching the CoC API, token verification, or snapshots.
- **`database-schema`** — migrations and schema changes.
- **`secure-feature`** — the checklist for adding any user-facing action (policy, validation, rate limit, audit).
- **`livewire-ui`** — building UI (Blade + Livewire + Tailwind): mobile-first, thin components, loading/empty/error states, accessibility.

The built-in `security-review` skill is useful before finishing a PR that touches auth, uploads, or the marketplace.

## Working style here

- Prefer editing existing files over creating new ones. Match the neighboring code in the module you touch.
- Run the relevant Pest tests before saying a task is done; report real results.
- Commit or push only when asked; branch off `master`.
- If a task requires a product decision that isn't in the specs, surface it — don't invent it.
