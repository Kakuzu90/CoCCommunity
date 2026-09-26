# 26 · Migration: Livewire + Alpine → Inertia + React

> **Status: proposed.** Adopting this reverses locked decision 2 in [README](README.md) ("Livewire 3 +
> Tailwind + Alpine. Not Inertia"). Nothing here takes effect until the decision is changed and the
> spec edits in §11 land. Until then, [06](06-tech-stack.md) and [18](18-design-system.md) remain the
> source of truth for new work.

## 1. Decision

Replace the Livewire 3 + Alpine presentation layer with **Inertia.js v2 + React 19 + TypeScript**,
rendered server-side (SSR) for every public route. Laravel stays the only backend: routing,
validation, authorization, sessions and every domain module are unchanged. Only `App\Livewire`,
Blade views and `resources/js` are rewritten.

What does **not** change:

| Kept as is | Why |
|---|---|
| Modular monolith, `App\Domain\*` modules, Deptrac rules between modules | Business logic already lives in services/actions ([05](05-architecture.md)); this migration is presentation-only by design ([06 §2](06-tech-stack.md)) |
| Session auth, database sessions, CSRF | Inertia is same-origin and cookie-based; no token auth, no public JSON API |
| Policies and Gates as the only authorization path | Controllers call `authorize()`; the client receives ability flags for display only |
| Tailwind 4, `tokens.css`, the visual design in [18](18-design-system.md) | Tokens are plain CSS custom properties; React components consume the same classes and variables |
| Media pipeline, presigned uploads, `Upload\IntentController` / `CompleteController` | Already JSON endpoints driven from client code |
| Game-asset rules (resolver, manifest, kill switch, unmodified) | The resolver moves to prop builders; templates still never build a `game/` path |
| PostgreSQL, no Redis, queues, schedule | Untouched |

## 2. Why, and what it costs

[06 §2](06-tech-stack.md) rejected Inertia for the MVP. This section restates that trade-off against
the code as it exists today, so the decision is made on current facts.

**Gains**

- One client-side model for interactive surfaces. Today interactivity is split across Livewire
  round-trips and twelve `Alpine.data` components in one 350-line `app.js` (`uiSelect`, `uiModal`,
  `uiTabs`, `uiTooltip`, `uiDropdown`, `uiCountUp`, `uiSentinel`, `avatarUploader`,
  `accountImageUploader`, `baseComposer`, `tagInput`, `unitFire`). Several bugs this session came
  from that seam (Alpine `$el` scoping in `uiModal`, stylesheet-blocked module scripts).
- Typed props. Page data becomes a TypeScript contract generated from PHP `Data` classes and enums,
  so a renamed field fails the type check instead of rendering blank.
- Component-level tests (Vitest + Testing Library) for UI logic that today is only reachable
  through Playwright.
- Upcoming Phase 3–5 surfaces (feed with filters, comments, search facets, composer with video,
  recruitment boards) are client-heavy; building them once in React avoids building them in
  Livewire first.

**Costs**

- **An SSR Node process in every environment.** Public pages must stay crawlable with full Open
  Graph cards ([06 §2](06-tech-stack.md), SEO surfaces in [25](25-development-phases.md)). That is a
  second long-running runtime to deploy, supervise and health-check (§8).
- **JS budget pressure.** NFR-PERF-6 caps a public page at 120 KB gzipped. The React runtime plus
  Inertia takes a large share of that before any page code. Step 0 measures it; §7 sets the rules.
- **Two stacks during the migration.** Livewire and Inertia coexist route by route (§9). Shell and
  navigation changes are made twice until the shell cutover.
- **Rewrite of the presentation tests.** 19 test files assert on rendered HTML (`assertSee`,
  `->blade()`), and 8 use `Livewire::test()`. They move to `assertInertia` and component tests (§6).

**Timing.** Phase 3 has shipped "Publishing + composer" only. "Feed, trending, landing pages",
"Search v1", "Moderation v1" and "SEO surfaces" are unbuilt. Migrating **before** those tasks means
they are built once; migrating after means rebuilding them. Recommended slot: immediately, as a
Phase 3 prerequisite task, before "Video processing".

## 3. Target stack

| Concern | Choice | Notes |
|---|---|---|
| Adapter | `inertiajs/inertia-laravel` v2 | Deferred props, polling, prefetch, history encryption |
| Client | `@inertiajs/react` v2, React 19 | Function components and hooks only |
| Language | TypeScript, `strict: true` | `tsc --noEmit` in CI |
| Build | Vite 7 + `laravel-vite-plugin` + `@vitejs/plugin-react` | Existing Vite config; add SSR entry |
| SSR | `php artisan inertia:start-ssr` (Node 22) | Required for public routes (§8) |
| Styling | Tailwind 4 + existing `resources/css/*.css` | Component CSS classes (`.ui-button`, `.unit-tile`) kept; no CSS-in-JS |
| Accessible primitives | Radix UI (headless, unstyled) for Dialog, Tabs, Select, Dropdown, Tooltip, Toggle | Replaces hand-rolled focus traps and roving tabindex; visuals stay ours ([18 §2.2](18-design-system.md)) |
| Routes in JS | `tightenco/ziggy` `route()` helper | Named routes only; no hard-coded URLs in components |
| PHP → TS types | `spatie/laravel-typescript-transformer` | Generates `resources/js/types/generated.d.ts` from `Data` classes and backed enums |
| Forms | Inertia `useForm` / `<Form>` | Server validation via Form Requests; errors arrive as props |
| Component tests | Vitest + `@testing-library/react` + jsdom | Co-located `*.test.tsx` |
| E2E | Existing Playwright + axe suite | Selectors by role and label, so most specs survive |
| Lint | ESLint (`typescript-eslint`, `react-hooks`, `jsx-a11y`) + Prettier | Added to `composer lint` equivalent in CI |

Removed at the end of the migration: `livewire/livewire`, Alpine (bundled by Livewire), every Blade
view except the Inertia root `app.blade.php` and mail templates.

## 4. Architecture changes

### 4.1 Request flow

```
Browser ──▶ Laravel route ──▶ Controller: validate (Form Request) → authorize → call service
                                   │
                                   ▼
                     Props builder (Data → array) ──▶ Inertia::render('Area/Page', props)
                                   │                         │
                         first load: SSR Node renders HTML   │ later visits: JSON props over XHR
```

Livewire components become **controllers + page components**. The five Livewire classes map to:

| Today | Target |
|---|---|
| `Livewire\Accounts\ManageAccounts` | `Web\AccountController@index` + `Pages/Accounts/Index.tsx`; attach, verify, detach, feature become POST/DELETE routes |
| `Livewire\Accounts\AccountDetail` | `Web\AccountController@show` + `Pages/Accounts/Show.tsx`; refresh becomes `POST /accounts/{ulid}/refresh` |
| `Livewire\Accounts\ManageDisputes` | `Web\DisputeController` + `Pages/Accounts/Disputes.tsx` |
| `Livewire\Admin\ResolveDispute` | `Admin\DisputeController@update` + `Pages/Admin/Disputes/Show.tsx` |
| `Livewire\Components\NotificationBell` | Shared prop `notifications.unread` + `usePoll(60_000)` on the bell only when the tab is visible ([16](16-notifications.md)) |

### 4.2 Props are built from Data, never from models

- Controllers pass **only** `App\Domain\*\Data` objects (or arrays built from them) to
  `Inertia::render`. Passing an Eloquent model is banned: it serializes every visible attribute and
  any loaded relation to the browser, which is how private fields leak.
- Each page has one props builder method (on a `Queries` class or a dedicated `*PageProps` class in
  the owning module's `Queries`), so the shape is testable without HTTP.
- Game assets are resolved server-side into `{ url, name, placeholderMark }` objects inside the props
  builder. React receives resolved assets; it never composes a `game/` URL. The template lint moves
  from Blade to `resources/js/**/*.tsx` (§6).
- **Abilities, not roles.** Display-only permission flags are computed with `Gate::allows()` in the
  props builder (`can: { refresh: true, detach: false }`). The server still authorizes every action.

### 4.3 Shared props (`HandleInertiaRequests`)

| Key | Content | Rule |
|---|---|---|
| `auth.user` | `{ id, username, displayName, avatarUrl, roles[] }` or `null` | Never email, status internals or timestamps |
| `flash` | `{ success?, error? }` | From session flash |
| `notifications.unread` | integer, capped display at 99+ | Lazy closure, so non-bell requests skip the query |
| `features` | kill switches such as `assets.enabled` | Read from config only |
| `ziggy` | route list filtered to the groups the page needs | No admin routes for non-staff users |

### 4.4 Security deltas ([11](11-security.md))

- **XSS.** React escapes text by default. `dangerouslySetInnerHTML` is banned except in one
  `SanitisedMarkdown` component that renders server-sanitised HTML (same rule as `{!! !!}` today).
  An ESLint rule enforces it.
- **CSRF.** Inertia uses the `XSRF-TOKEN` cookie via axios; `VerifyCsrfToken` is unchanged.
- **CSP.** Not implemented yet in `app/` (no header middleware exists). Add it in step 0: nonce-based
  `script-src` with `Vite::useCspNonce()`, no `unsafe-inline`, no `unsafe-eval`. React needs no
  `unsafe-eval`; this is easier than the Alpine case in [11](11-security.md).
- **History encryption.** `Inertia::encryptHistory()` on every authenticated route group, and
  `Inertia::clearHistory()` on logout, so the back button cannot show another user's cached props.
- **Props exposure test.** A security test renders each page as guest, member and owner and asserts
  the props contain no key from a denylist (`email`, `password`, `remember_token`, `raw_payload`,
  `ip`, `user_agent`).
- **Authorization enumeration.** The existing route-authorization test ([11](11-security.md)) keeps
  working, since actions become normal routes instead of Livewire method calls.

### 4.5 Deptrac

- The HTTP layer is `App\Http` only; the `App\Livewire` layer is deleted.
- New rule: `App\Http\Controllers` may depend on `Inertia\*`; `App\Domain\*` may not.
- `App\Domain\*` still never depends on `App\Http`.

## 5. Front-end structure and component mapping

```
resources/js/
├── app.tsx                 # client entry: createInertiaApp, page resolver, progress bar
├── ssr.tsx                 # SSR entry
├── pages/{Area}/{Action}.tsx     # one per Inertia::render name, e.g. pages/Accounts/Show.tsx
├── layouts/                # AppLayout, AuthLayout, AdminLayout (persistent layouts)
├── components/
│   ├── ui/                 # Button, Input, Select, Modal, Tabs, ... (the x-ui.* set)
│   ├── game/               # GameAsset
│   ├── player/             # PlayerCard, UnitTile, VerifiedBadge
│   ├── layout/             # TopBar, PrimaryNav, BottomNav, Sidebar, Footer, Brand, AdminNav
│   └── notifications/      # NotificationItem, NotificationBell
├── hooks/                  # usePresignedUpload, useCountUp, useInView, usePrefersReducedMotion
├── lib/                    # fire renderer (unit-fire), formatters
└── types/                  # generated.d.ts + hand-written page prop types
```

Naming: components PascalCase files; page names match `Inertia::render('Accounts/Show')` exactly;
hooks `useX`. One component per file.

### 5.1 `x-ui.*` primitives (27)

| Blade | React | Built on |
|---|---|---|
| `alert`, `badge`, `pill`, `card`, `avatar`, `icon`, `icons`, `skeleton`, `stat-block`, `empty-state`, `progress` | same names, PascalCase | Plain components, existing CSS classes |
| `button`, `input`, `textarea`, `checkbox`, `radio`, `field` | `Button`, `Input`, ... `Field` | Native elements; `Field` wires label, help and error ids |
| `select` (Alpine `uiSelect`, searchable) | `Select` | Radix Select + filter input; native `<select>` fallback kept for SSR/no-JS |
| `modal` (Alpine `uiModal`) | `Modal` | Radix Dialog; sheet variant under 768px |
| `tabs` (Alpine `uiTabs`, linkable) | `Tabs` | Radix Tabs; `linkable` syncs the URL hash |
| `dropdown`, `menu-item` (Alpine `uiDropdown`) | `Dropdown`, `MenuItem` | Radix DropdownMenu |
| `tooltip` (Alpine `uiTooltip`) | `Tooltip` | Radix Tooltip |
| `toggle` | `Toggle` | Radix Switch |
| `toast` | `Toaster` | Reads `flash` shared prop |
| `pagination`, `load-more` (Alpine `uiSentinel`) | `Pagination`, `LoadMore` | Inertia v2 `<WhenVisible>` / infinite scroll |

### 5.2 Feature components and Alpine data

| Today | React |
|---|---|
| `<x-game.asset>` | `GameAsset` taking a resolved asset prop; placeholder rendering unchanged |
| `<x-player.card>` (hero, standard, compact, mini) | `PlayerCard` with the same four variants |
| `<x-player.unit>` + `unitFire` canvas | `UnitTile` + `FireRing` (the shared renderer in `lib/unit-fire.ts`, started in `useEffect`) |
| `village-progression` partial | `VillageProgression` inside `Tabs` |
| `uiCountUp` | `useCountUp` (respects reduced motion) |
| `avatarUploader`, `accountImageUploader` | `usePresignedUpload` hook + `AvatarUploader`, `AccountImageUploader` |
| `baseComposer`, `tagInput` | `BaseComposer`, `TagInput` using `useForm` |
| Component gallery `/dev/components` | `pages/Dev/Components.tsx`, non-production only, same sections |

## 6. Testing

| Today | Target |
|---|---|
| `Livewire::test(...)` (8 files) | HTTP feature tests against the new routes, plus `assertInertia` on the page |
| `assertSee` on rendered HTML (19 files) | `assertInertia(fn (AssertableInertia $page) => $page->component('Accounts/Show')->where('account.ign', 'Night Chief'))`; visual text checks move to Vitest |
| `->blade()` component tests (`GameAssetComponentTest`) | Vitest tests for `GameAsset`; PHP test keeps the resolver side |
| `ConventionsTest`: "each Livewire page has a feature test" | "each `Inertia::render` component name has a page file and a feature test" |
| `GameAssetTemplateLintTest` (Blade) | Same lint over `resources/js/**/*.{ts,tsx}` |
| Playwright + axe (`accounts`, `components`, `disputes`, `notifications`, `shell`) | Kept; update selectors that relied on Livewire attributes |
| n/a | Props exposure security test (§4.4); SSR smoke test that fetches each public route with JS disabled and asserts the `<title>`, OG tags and main heading are in the HTML |

Every existing assertion on behaviour (authorization, validation, 404 for foreign ids, privacy
rules) must survive the move with the same meaning. A migrated route is not done while any of its
old assertions is deleted rather than ported.

## 7. Performance budgets

| Budget | Today | Target rule |
|---|---|---|
| NFR-PERF-6, JS on a public page < 120 KB gz | Livewire + Alpine bundle | Same number. Pages are code-split by the page resolver (`import.meta.glob` lazy). Radix parts are imported per component. A bundle-size check fails CI per entry chunk |
| NFR-PERF-8, Livewire round-trip p95 < 250 ms | Livewire updates | Replaced by "Inertia visit (JSON props) p95 < 250 ms", measured server-side per route |
| NFR-PERF-4, LCP < 2.5 s on 4G | Server-rendered Blade | SSR for public routes; hydration must not re-fetch props |
| NFR-PERF-5, CLS < 0.1 | Intrinsic media sizes | Unchanged; `GameAsset` and images keep explicit width/height |
| NFR-PERF-7, ≤ 25 queries | Unchanged | Deferred props for below-the-fold sections (progression, images) so the first response stays small |

If step 0 shows React + Inertia alone exceed half the JS budget, stop and bring the numbers back to
this spec before continuing. Do not raise the budget silently.

## 8. SSR operations

- `docker-compose`: new `ssr` service (Node 22) running `php artisan inertia:start-ssr` against the
  built `bootstrap/ssr/ssr.js`; the `app` service reaches it on the internal network.
- Production: the SSR process runs under the same supervisor as the queue workers, restarted on
  deploy after `npm run build`.
- Health: the ops health endpoint ([Ops task in 25](25-development-phases.md)) adds an SSR check.
  If SSR is down, Inertia falls back to client rendering, so pages still work, but crawlers get an
  empty shell. That state alerts; it is not silent.
- SSR runs for every route (simplest, consistent markup). Admin and settings could be excluded
  later if SSR load matters; not before measuring.
- Dev: `npm run dev` for HMR without SSR; `composer dev` starts SSR too when testing SEO output.
- The current Vite dev-server polling cost on the Windows bind mount (first CSS compile takes
  minutes) gets worse with a React toolchain. Step 0 excludes `resources/game-assets/`,
  `storage/` and `vendor/` from `server.watch` and measures cold start.

## 9. Migration plan

Strangler pattern: one route group at a time, both stacks live, Livewire removed last. Each step
ships behind nothing (no feature flag); a route switches when its parity checklist passes.

**Coexistence rule.** The Inertia root view (`resources/views/app.blade.php`) and the Blade layouts
(`components/layouts/*`) both exist until step 6. Shell changes during that window are made in both.
Step 1 moves the shell early to keep that window short.

| Step | Scope | Exit criteria | Size |
|---|---|---|---|
| **0. Foundation** | Install adapter, React, TS, Vite React plugin, SSR entry, Ziggy, type transformer, ESLint, Vitest; `HandleInertiaRequests`; CSP middleware; `app.blade.php`; `ui/*` primitives; `/dev/components` gallery in React | Gallery page renders every primitive with SSR; bundle and cold-start numbers recorded in this spec; CI runs `tsc`, ESLint, Vitest | L |
| **1. Shell + public pages** | `AppLayout`, `AuthLayout`, top bar, navs, footer, brand; `home`, placeholder routes (`bases.index`, `recruit.index`, `search`), `profile.show` | SSR smoke test passes on public routes; Playwright `shell.spec` green; OG tags present with JS disabled | M |
| **2. Auth** | login, register, verify-email, check-email, forgot/reset password, Turnstile widget | Auth feature and security tests ported; Turnstile renders under CSP | M |
| **3. Settings + notifications** | profile, avatar upload, privacy, security, sessions, deletion; `notifications.index`; `NotificationBell` with `usePoll` | Upload flow works against MinIO; bell polls only while visible | M |
| **4. Accounts** | `ManageAccounts`, `AccountDetail` (village tabs, unit tiles, fire ring, equipment dialog), `ManageDisputes`, account images | All `PlayerAccounts` tests ported with the same assertions; Livewire account classes deleted | L |
| **5. Admin + bases** | dashboard, users, sanctions, audit log, disputes (`ResolveDispute`); `bases.create` composer, `bases.submitted` | Admin authorization tests ported; composer publishes through the existing service | L |
| **6. Removal** | Delete `App\Livewire`, Livewire views, Blade layouts and `x-ui` Blade components, Alpine code; remove `livewire/livewire`; update Deptrac | No Livewire reference in `app/`, `resources/`, `tests/`, `composer.json`; full CI green on SQLite and Postgres | S |

Each step follows the normal [25 §3](25-development-phases.md) task template and definition of done.
One step per session, as with phase tasks.

### 9.1 Per-route parity checklist

- [ ] Same URL, same route name, same HTTP method for every action.
- [ ] Same authorization: guest, other user, owner, staff each get the same status code as before.
- [ ] Same validation messages and field names.
- [ ] Every state in [18](18-design-system.md) (empty, loading, error, stale, locked) present.
- [ ] Keyboard and screen reader behaviour at least as good: focus order, dialog focus trap and
      return, labelled controls; axe clean.
- [ ] Reduced motion respected (count-up, fire ring, dialog transitions).
- [ ] Query count at or below the Livewire version.
- [ ] SSR HTML contains the page title and main content (public routes).
- [ ] Component gallery updated with any new variant.

### 9.2 Rollback

Until step 6, a migrated route can be pointed back at its Livewire component by reverting the route
definition; the Livewire class is deleted only in step 4/5 after its tests pass in the new form,
and wholesale only in step 6.

## 10. Edge cases

| Case | Expected behaviour |
|---|---|
| SSR process down | Client render fallback; health check fails and alerts; public pages still usable |
| JS fails to load | SSR HTML is readable; forms that must work without JS (login, register, reset) are plain `<form method="post">` that Inertia enhances |
| Session expires mid-visit | Inertia receives 419/401; the client redirects to login with the intended URL kept |
| Back button after logout | History encryption cleared on logout; no stale private props shown |
| Stale asset build after deploy | Inertia asset versioning (`version()` from the Vite manifest hash) forces a full reload |
| Validation error on a modal form | Errors stay in the dialog; focus moves to the first invalid field |
| Kill switch `assets.enabled = false` | Props builders return placeholders; no React code path can show a game asset |
| Very large progression payload | Progression is a deferred prop; the card renders first |

## 11. Spec edits required on approval

| File | Change |
|---|---|
| [README](README.md) | Locked decision 2 → "Laravel + Inertia v2 + React 19 (TypeScript) + Tailwind 4, SSR for public routes. Not Livewire, not a split SPA with its own API." Add this file to the reading order and load map ("Any UI": 18 + 26) |
| [06](06-tech-stack.md) | §1 frontend row and admin row; §2 recommendation flips to Option B with this spec's rationale; SSR and Radix added to the stack table; Livewire moves to "considered" |
| [03](03-non-functional-requirements.md) | NFR-PERF-8 reworded to Inertia visits; add an SSR availability requirement |
| [04](04-roles-and-permissions.md) | "controller/Livewire action" → controller; `@can` → ability props; session note about Livewire removed |
| [05](05-architecture.md) | Request-flow diagram (§4.1 here); HTTP layer description; Admin row; composer flow; validation row |
| [11](11-security.md) | XSS rule for React and `dangerouslySetInnerHTML`; CSP for Vite nonces; CSRF via XSRF cookie; props exposure test; history encryption; remove "Livewire endpoints" wording |
| [13](13-claiming-workflow.md) | Implementation notes naming `ManageAccounts` and `ResolveDispute` Livewire classes |
| [16](16-notifications.md) | `wire:poll.60s.visible` → Inertia `usePoll` with visibility check |
| [17](17-search-and-discovery.md) | "Livewire search UI" → React search page with partial reloads |
| [18](18-design-system.md) | §7 implementation: React components under `resources/js/components`, Radix primitives, gallery page; the Select/Modal/Tabs notes; remove "Livewire's ESM bundle supplies Alpine" |
| [19](19-module-structure.md) | Folder tree (remove `app/Livewire`, `View/Components`; add `resources/js` tree from §5); naming table; dependency rules; "every Livewire page has a test" convention; task checklist items 7–8 |
| [22](22-scaling.md) | "Livewire filter update" budget row → Inertia partial reload |
| [24](24-risks-and-assumptions.md) | R12 and A13 replaced by: SSR availability, JS budget, dual-stack window |
| [25](25-development-phases.md) | Insert this migration as the next Phase 3 task; task template "Livewire page" → "Inertia page + props builder"; design-system row names React primitives |
| `CLAUDE.md` | Locked decision 2; stack table and commands (`ssr` service, `npm run typecheck`, `npm test`); definition of done adds `tsc`, ESLint, Vitest |
| `DOCKER.md` | `ssr` service, Node version, watch exclusions |

`.claude/hooks/require-uiux.sh` already matches `*.tsx` and `*.jsx`; no change needed.

## 12. Risks and open questions

| Risk | Mitigation |
|---|---|
| JS budget exceeded on public pages | Measured in step 0 with a stop rule (§7); lazy page chunks; no UI kit beyond headless Radix |
| SSR adds an operational failure mode | Client fallback, health check, alert (§8) |
| Dual-stack window drags on | Shell moves in step 1; one step per session; no new Livewire code after step 0 |
| Accessibility regression from rewriting hand-built widgets | Radix primitives plus the existing axe suite and the parity checklist |
| Props leak private data | Data-only props rule (§4.2) and the props exposure test (§4.4) |

Open questions for the owner, with the default this spec assumes:

1. **TypeScript**: assumed yes, `strict`.
2. **Route helper**: assumed Ziggy. Laravel Wayfinder (typed route functions) is the alternative if
   its API is stable when step 0 starts.
3. **SSR scope**: assumed all routes; could be public routes only.
4. **Headless primitives**: assumed Radix UI; React Aria is the alternative with stronger built-in
   accessibility at a larger bundle cost.
