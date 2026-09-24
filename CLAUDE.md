# Clash Commons — working agreement

A Clash of Clans community platform: verified in-game account ownership, base-layout sharing,
recruitment, and later a services marketplace. Laravel app lives in `src/`.

**Current state:** Laravel 12 is scaffolded in `src/` with Livewire 3, Tailwind 4, Pest,
Pint, PHPStan/Larastan and Deptrac. See `DOCKER.md` for setup and CI commands.
Domain module providers and shared value objects, casts, validation rules, queue names and
immutable dates are in place. Design tokens and `x-ui.*` primitives are available in the
non-production gallery at `/dev/components`. The app shell and remaining Phase 0 tasks are not implemented.

## Starting a session

Copy one of these. The task name must match a row in `specs/25-development-phases.md` §4.

**Pick up the next piece of work:**
```
Read CLAUDE.md and specs/README.md. Check the progress checklist below,
tell me the next unchecked task, then do it. One task only.
```

**Do a specific task:**
```
Do the Phase 0 "Media pipeline" task. Load its Core + Plus bundle
from specs/25-development-phases.md §4. Stop when its tests pass.
```

**Continue something half-finished:**
```
Read CLAUDE.md, then `git log -5` and `git status` to see where we left off.
Summarise the state and what remains on the current task before writing code.
```

**Plan rather than build** (use when the task is large or the spec looks thin):
```
Do not write code. Read the bundle for the Phase 2 "Attach + token verification"
task and give me a task breakdown using the template in specs/25 §3.
```

**Change the plan:**
```
I want to change <decision>. Find every spec affected, tell me what breaks,
and update them. No code.
```

At the end of every session, ask the agent to tick the checklist below and commit.

## Progress checklist

Update this as tasks complete. It is the fastest way to reorient after time away.

### Phase 0 — Foundation
- [x] Project setup, CI, static analysis
- [x] Domain skeleton + `Support` primitives
- [x] Design tokens + `x-ui.*` primitives
- [x] App shell, layouts, navigation
- [x] Media pipeline
- [x] GameAssets module + asset policy
- [x] Ops: health, logging, error tracking, workers

### Phase 1 — Identity
- [x] Registration, login, verification, reset
- [x] Roles, status, policy scaffold
- [x] Profiles + avatar upload
- [ ] Privacy settings + public profile
- [ ] Settings area (sessions, deletion)
- [ ] Admin v1 + audit log
- [ ] Notifications v1

### Phase 2 — Verified CoC accounts
- [ ] API client, decorators, key pool
- [ ] Attach + token verification flow
- [ ] Conflicts, disputes, ownership transfer
- [ ] Tiered sync + snapshots
- [ ] PlayerCard, account detail, progression
- [ ] Asset pack v1
- [ ] Profile v2

### Phase 3 — Bases + moderation (MVP ships here)
- [ ] Publishing + composer
- [ ] Video processing
- [ ] Feed, trending, landing pages
- [ ] Likes, bookmarks, comments, counters
- [ ] Search v1
- [ ] Moderation v1
- [ ] SEO surfaces

Phases 4–7: see `specs/25-development-phases.md`. Do not start them before Phase 3 ships.

### Local-first: no external accounts needed to build

Every external dependency has a local stand-in. Build against the stand-in, swap by `.env` later.
If a task appears to need a real account, that is a design smell — check the spec first.

| External | Local stand-in | Swap when |
|---|---|---|
| Cloudflare R2 + CDN | `minio` container, profile `storage` (S3-compatible, real presigned URLs) | Before public launch |
| Clash of Clans API | `FakeCocApiClient` + recorded fixtures (`COC_DRIVER=fake`) | Before Phase 2 ships to staging |
| Mail provider | `mailpit` container | Before Phase 1 ships to staging |
| Cloudflare Turnstile | Cloudflare's public always-pass test keys | Before public launch |
| Sentry | Log channel | Any time |
| Game asset pack | Placeholder + label from `GameAssetResolver` | Before Phase 2 asset pack v1 |

### Blocked on you (not the agent), in the order they bite

- [ ] Curated game asset pack — Phase 2 "Asset pack v1"
- [ ] CoC API key from developer.clashofclans.com, bound to the staging egress IP — before Phase 2 staging
- [ ] R2 bucket + CDN domain — before Phase 3 public launch
- [ ] Mail provider (Postmark/SES) with SPF, DKIM, DMARC — before public launch
- [ ] Q1 operating jurisdiction / legal entity — before public launch
- [ ] Q2 domain name and branding — before public launch
- [ ] Remaining open questions: `specs/24-risks-and-assumptions.md` §3

## Specs are the source of truth

`specs/` holds the complete plan. Read before writing code, and update it when the implementation
diverges — the spec outlives the ticket.

**Always load:**
- `specs/README.md` — index, load map, locked decisions
- `specs/05-architecture.md` — modules, boundaries, event seam
- `specs/19-module-structure.md` — folders, naming, dependency rules
- `specs/04-roles-and-permissions.md` — every write surface needs a policy

**Then load the task's bundle from `specs/25-development-phases.md` §4**, which maps each phase
task to its Core + Plus specs. For work that is not a phase task, use the layer table in
`specs/README.md` → Load map.

**Every task additionally pulls:** the `FR-*` rows it satisfies from `specs/02-functional-requirements.md`,
its domain's rows from `specs/23-edge-cases.md`, and `specs/11-security.md` if the surface takes
user input, files, or crosses a trust boundary.

Do not load the spec set in numerical order. It is a reading order for people, not a load order.

## Scope discipline

One phase task per session. Finish it, make its tests pass, stop. Do not drift into adjacent tasks
in the same phase — say what is next instead.

## Locked decisions

Changing one of these means revisiting specs, not just code.

1. Modular monolith. Laravel 12 / PHP 8.3. One deployable.
2. Livewire 3 + Alpine + Tailwind 4. Not Inertia, not a split SPA.
3. PostgreSQL 16 is the only datastore — including cache, queue and session tables.
4. **No Redis.** All code goes through `Cache` / `Queue` facades. No `Redis::`, no driver-specific
   calls, no cache tags. Migration triggers in `specs/21-caching-strategy.md`.
5. Cloudflare R2 + CDN for all media. No binaries in the database.
6. Supercell compliance: game assets may identify game content, **unmodified**; our branding, UI,
   badges and illustrations stay original. Full rule in `specs/18-design-system.md` §2.
7. No payment processing in the marketplace. See `specs/15-marketplace-workflow.md`.

## Non-negotiables in code

- **Authorization** goes through Policies/Gates only. `@can` in Blade is display; the server
  re-checks on action. Owned resources are query-scoped so a foreign id 404s.
- **No `$guarded = []`.** Explicit `$fillable`. `role`, `status`, counters and `user_id` are never
  fillable.
- **No string interpolation into SQL.** `DB::raw` needs a justification comment.
- **Business logic lives in services/actions**, not in Livewire components or controllers.
  Controllers and components: validate → call a service → render.
- **Cross-module access** via another module's `Contracts`/`Services`/`Data`/`Events` — never its
  Eloquent models. Enforced by Deptrac in CI.
- **User uploads** always go through the media pipeline (`specs/10-media-storage.md`). Game assets
  never do — they are served byte-exact from the `game/` R2 prefix (`specs/10-media-storage.md` §11).
- **Value objects** for anything with rules: `PlayerTag`, `BaseLink`, `LayoutHash`, `ThLevel`.
- **Backed enums** for every status column.
- **No magic numbers.** Limits, weights and windows are config keys (`specs/19-module-structure.md` §5).
- **Jobs are idempotent**, take ids not models, and are chunked when fanning out.
- **No impersonation feature.** Ever.

## Stack & commands

Docker compose (details in `DOCKER.md`):

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan test --compact
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/phpstan analyse
docker compose --profile storage up -d          # MinIO — start for any media work
docker compose --profile assets up -d node      # Vite
docker compose --profile tools up -d adminer    # DB GUI :8081
```

App http://localhost:8080 · Mail http://localhost:8025 · Storage console http://localhost:9001
· DB GUI http://localhost:8081

## Definition of done

- Feature test: happy path, authorization, validation.
- Security test if the surface takes input, files, or crosses a trust boundary.
- New UI variants added to the component gallery at `/dev/components`.
- Pint clean, PHPStan clean (L6, L8 on `app/Domain`), Deptrac clean.
- CI green on both SQLite and Postgres.
- `specs/` updated if the implementation diverged from the plan.
