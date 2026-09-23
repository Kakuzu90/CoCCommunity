# AGENTS.md

Canonical engineering rules for any AI agent (Codex, Claude, others) working in this repo. Read this before writing code. Claude Code users: `CLAUDE.md` points here.

## What this is

A Clash of Clans community platform: a **Laravel modular monolith**. Players link CoC accounts (verified by in-game API token), share base layouts, recruit, and later trade permitted services. Full design is in [`specs/`](specs/) — start with [`specs/README.md`](specs/README.md).

The Laravel app lives in [`src/`](src/); local dev runs on Docker — see [`DOCKER.md`](DOCKER.md). Design tokens (palette, fonts) are in [`specs/12-design-system.md`](specs/12-design-system.md).

**The plan is authoritative.** If code and specs disagree, the specs win — or you change the spec in the same PR and say why. Do not silently diverge.

## Non-negotiables (from the specs)

1. **No Redis.** Cache and queue use Laravel's `database`/`file` drivers. Never add a Redis dependency, `predis`, or `phpredis` config without an explicit spec change. See [`specs/10-infrastructure.md`](specs/10-infrastructure.md).
2. **No media in the database.** Files live in R2; the DB stores only a `media` row (path, mime, size, checksum, status). See [`specs/05-media-storage.md`](specs/05-media-storage.md).
3. **Never call the CoC API in a request path.** All external calls go through queued jobs behind the `ClashClient` interface, reading from `coc_account_snapshots`. See the `coc-integration` skill and [`specs/04-coc-integration-and-claiming.md`](specs/04-coc-integration-and-claiming.md).
4. **`coc_accounts.tag` is globally unique.** One verified owner per tag. Ownership transfers go through the claim/dispute flow and always write an `audit_logs` row.
5. **No marketplace payments or fund custody.** Marketplace is listings + messaging + reviews only until a spec change adopts Stripe Connect. Never write code that holds or moves money.
6. **Authorization is policy-first.** Every user-facing mutation is gated by a Model Policy. No ad-hoc `if ($user->id === ...)` scattered in controllers or Blade.

## Architecture rules

- Modules live under `app/Modules/<Domain>/` (see [`specs/02-architecture-and-stack.md`](specs/02-architecture-and-stack.md) for the list and internal layout).
- A module **owns its tables and migrations**. Other modules read through its Service class or a read model — never by importing its Eloquent models directly.
- Cross-module side effects go through domain events (`AccountVerified`, `BaseLiked`, `ReportFiled`), not direct calls into another module's internals.
- Controllers/Livewire components stay thin: authorize, hand validated data to a Service or Action, return the response. Business logic lives in `Services/` or `Actions/`, not in HTTP classes.
- **Validation lives in a `FormRequest` only.** Controllers never call `$request->validate()` or `Validator::make()` inline. A controller action type-hints its `FormRequest` and reads `$request->validated()`. (Livewire: use a `#[Validate]`/`rules()` definition on the component or a dedicated form object — never validate inside the action method body.)

## Coding conventions

- **PHP 8.3+, strict types.** `declare(strict_types=1);` in every PHP file. Typed properties, typed params and returns.
- **Follow Laravel conventions and PSR-12.** Run `./vendor/bin/pint` before committing; do not hand-format.
- **Mass assignment:** explicit `$fillable`; pass `$request->validated()` (never `$request->all()`) into models/actions. `Model::create($request->all())` is banned.
- **Queries:** Eloquent or the query builder with bindings. No raw string-interpolated SQL, ever.
- **Money/counts:** integers, not floats. Denormalized counters (`like_count`, `view_count`) are updated by jobs/events, never with live `count(*)` on hot paths.
- **Enums:** PHP backed enums for account states, report status, order status, etc. Not string literals sprinkled around.
- **Time:** store UTC; `CarbonImmutable`.
- **Naming:** classes match Laravel idioms (`SyncCocAccount` job, `BasePolicy`, `CreateBaseLayout` action). Match the naming already in the module you touch.

## Testing

- Pest. Every feature adds tests: at least one happy path and the authorization-denied path.
- **Authorization tests are mandatory** for anything touching user-owned data — prove a non-owner and the wrong role are rejected (IDOR guard).
- External APIs (CoC, R2, Stripe) are faked/mocked in tests. No live network calls in the suite.
- Run the relevant tests before claiming a task is done. If you didn't run them, say so.

## Security checklist (apply to every user-facing change)

Grounded in [`specs/06-auth-and-security.md`](specs/06-auth-and-security.md):
- Policy check on the resource by ownership + role.
- Input validated via a Form Request; output escaped (Blade auto-escape; sanitize any rich text).
- Rate-limit state-changing and enumerable endpoints.
- No secrets in code or logs; the stored CoC API token is encrypted at rest.
- File uploads: signed URL, MIME + magic-byte signature check, size/count limits, re-encode, scan. Never trust the extension.
- Sensitive mutations write an `audit_logs` row.

## Git & PR

- Branch off `master`; do not commit to `master` directly.
- Conventional-ish commit subjects (`feat(bases): ...`, `fix(coc): ...`). Explain *why* in the body when it isn't obvious.
- One logical change per PR. If you spot unrelated cleanup, note it — don't bundle it.
- Only commit or push when asked.

## Anti-slop rules (do not skip)

This project must not read like generated filler. Specifically:

- **No narration comments.** Delete `// loop through users`, `// create the model`, `// return response`. Comment only *why* something non-obvious is done, never *what* the line plainly says.
- **No restating the framework.** Don't wrap Laravel features in pointless helpers or re-document them.
- **No dead scaffolding.** Don't leave `TODO`, commented-out blocks, unused imports, empty catch blocks, or example/placeholder code in a PR. Delete what you don't use.
- **No speculative abstraction.** Build for the current spec, not imagined future needs. No interface with one implementation "just in case" (the `ClashClient` boundary is the deliberate exception, and it's in the spec).
- **No boilerplate READMEs or docblocks** that repeat the method signature. A docblock earns its place only when it says something the signature can't.
- **Match the existing code.** Before writing, read a neighboring file in the same module and mirror its structure, naming, and error handling. Consistency over personal preference.
- **Say what you actually did.** Report real test results and skipped steps honestly. Don't claim "fully tested" or "production-ready" — describe what's covered and what isn't.
- **Small, real diffs.** Prefer editing existing code to duplicating it. If a change balloons, stop and flag it.

## When unsure

Ask, or state your assumption explicitly and proceed. Do not invent product decisions (pricing, limits, moderation policy) that aren't in the specs — surface them instead.
