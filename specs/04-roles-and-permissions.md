# 04 — Roles, Permissions & Auth Strategy

## 1. Roles

Four roles, stored as a single `users.role` enum column. **Deliberately not a package.**
Spatie's permission package is excellent but adds four tables and a mental model we do not need
for four fixed roles; if per-user granular permissions become necessary (e.g. "media-only
moderator"), we migrate then, not now.

| Role | Value | Who | Granted by |
|---|---|---|---|
| User | `user` | Every registered account | Automatic |
| Moderator | `moderator` | Trusted community volunteers | Admin |
| Admin | `admin` | Staff | Super admin |
| Super Admin | `super_admin` | Founders / platform owners | Seeded, changeable only via console command |

Roles are hierarchical: each level includes everything below it, except where a rule explicitly
says otherwise (e.g. moderators cannot suspend users; admins cannot change roles).

### Account status (orthogonal to role)

`users.status`: `active`, `restricted`, `suspended`, `banned`, `pending_deletion`.

| Status | Can log in | Can read | Can write | Notes |
|---|---|---|---|---|
| `active` | yes | yes | yes | Normal |
| `restricted` | yes | yes | no publishing/commenting/messaging | Soft sanction, time-boxed |
| `suspended` | yes | own data only | no | Sees a suspension notice with end date and appeal link |
| `banned` | no | no | no | Content hidden, tags released after 30 days |
| `pending_deletion` | yes (cancels deletion) | yes | no | 30-day window |

Additional flags gating capabilities: `email_verified_at` (required for any write),
`has_verified_coc_account` (required to publish bases, recruit, or sell).

## 2. Permission matrix

`○` = own resource only. `✓` = any resource. `–` = no.

| Capability | User | Moderator | Admin | Super Admin |
|---|---|---|---|---|
| View public content | ✓ | ✓ | ✓ | ✓ |
| Edit own profile / privacy | ○ | ○ | ○ | ○ |
| Attach / verify / detach CoC account | ○ | ○ | ○ | ○ |
| Publish / edit / delete base | ○ | ○ | ○ | ○ |
| Like / bookmark / comment | ✓ | ✓ | ✓ | ✓ |
| Report content | ✓ | ✓ | ✓ | ✓ |
| Create recruitment post | ○ | ○ | ○ | ○ |
| Apply to recruitment post | ○ | ○ | ○ | ○ |
| View report queue | – | ✓ | ✓ | ✓ |
| Claim / assign a report case | – | ✓ | ✓ | ✓ |
| Hide content | – | ✓ | ✓ | ✓ |
| Remove content permanently | – | – | ✓ | ✓ |
| Warn user | – | ✓ | ✓ | ✓ |
| Restrict user (≤7 days) | – | ✓ | ✓ | ✓ |
| Suspend user | – | – | ✓ | ✓ |
| Ban user | – | – | ✓ | ✓ |
| Unban / lift sanction | – | – | ✓ | ✓ |
| Review media quarantine queue | – | ✓ | ✓ | ✓ |
| Resolve ownership dispute | – | – | ✓ | ✓ |
| Force ownership transfer | – | – | ✓ | ✓ |
| Approve marketplace seller | – | – | ✓ | ✓ |
| Resolve marketplace dispute | – | – | ✓ | ✓ |
| Manage tags / categories | – | – | ✓ | ✓ |
| View moderation log | – | ○ own actions | ✓ | ✓ |
| View audit log | – | – | ✓ | ✓ |
| Change user roles | – | – | – | ✓ |
| Manage feature flags / settings | – | – | – | ✓ |
| Hard-delete a user | – | – | – | ✓ |
| Impersonate a user | – | – | – | – (never) |

**Two structural rules:**
1. A moderator can never act on another moderator-or-above's content or account. Escalation only.
2. Every row above `Report content` requires the actor to record a reason; the reason is mandatory
   at the service layer, not merely in the form.

An actor is never listed in their own management surface: the admin user list (Admin v1) excludes the
viewing account. Acting on oneself is already impossible (`UserPolicy::outranks()` returns false for
self), and a staff member manages their own account through Settings, not the moderation tools.

## 3. Authorization strategy

### Policies as the only source of truth

- One Policy per authorizable model: `UserPolicy`, `ProfilePolicy`, `CocAccountPolicy`,
  `BaseLayoutPolicy`, `BaseCommentPolicy`, `RecruitmentPostPolicy`, `ApplicationPolicy`,
  `ListingPolicy`, `OrderPolicy`, `ReportPolicy`, `MediaPolicy`.
- Every controller/Livewire action calls `authorize()` / `$this->authorize()`. **No implicit trust
  from route grouping alone** — route middleware is defence in depth, not the check.
- Blade uses `@can` for display; the server re-checks on action. Hiding a button is not authorization.
- Admin-only abilities live in Gates: `access-admin`, `manage-roles`, `resolve-disputes`,
  `view-audit-log`.
- `Gate::before` grants super admin everything except the explicitly denied (`impersonate`).

### IDOR prevention

- All owned-resource queries are scoped at the query level (`->whereBelongsTo($user)`), so a wrong
  id 404s before a policy ever runs.
- Public identifiers: users are addressed by `username`, bases by `ULID` slug
  (`{ulid}-{slug}`), never by autoincrement id in any URL.
- Route model binding uses explicit scoped bindings for nested resources
  (`/bases/{base}/comments/{comment}`), so a comment from another base cannot be targeted.

### Write gating middleware

Three middlewares, applied in order, each with a dedicated denial page:
1. `EnsureEmailIsVerified` — blocks all writes.
2. `EnsureAccountIsActive` — blocks `restricted`, `suspended`, `banned`, `pending_deletion` writes.
3. `EnsureHasVerifiedCocAccount` — blocks publishing, recruiting and selling.

### Implementation notes (Phase 1 — roles/status/policy scaffold)

- **Role hierarchy** lives on the `UserRole` enum (`level()`, `atLeast()`); status predicates live on
  `UserStatus` (`canAuthenticate()`, `canWrite()`). Comparisons are by level, never equality.
- **One source of truth for the matrix.** The role-gated abilities of §2/§3 are an `Ability` enum
  (`App\Domain\Auth\Enums`), each case carrying its `minimumRole()`. `AuthServiceProvider` defines a
  Gate per case from that mapping, so the gates cannot drift from the matrix. The four named gates in
  §3 (`access-admin`, `manage-roles`, `resolve-disputes`, `view-audit-log`) are cases of this enum;
  `manage-roles` == changing roles, `resolve-disputes` covers ownership and marketplace disputes. The
  matrix test (`tests/Security/PermissionMatrixTest`) asserts every role × ability against a table
  transcribed independently from §2.
- **`Gate::before`** grants super admin every ability except `impersonate`, which is denied to
  everyone (locked decision) by returning null there so the always-false ability gate stands.
- **Policies** (`UserPolicy` today) live in `App\Domain\Auth\Policies` and are registered via
  `Gate::policy` in `AuthServiceProvider`. They are never referenced statically by Presentation
  (Deptrac keeps Presentation off a module's internals); controllers/components call `authorize()`
  and the Gate resolves the policy. `UserPolicy` encodes structural rule 1 (`outranks()`: act only on
  a strictly lower role, never self).
- **Write-gating + role middleware** are four aliases registered centrally in `bootstrap/app.php`:
  `verified` (`EnsureEmailIsVerified`), `active` (`EnsureAccountIsActive`), `coc.verified`
  (`EnsureHasVerifiedCocAccount`) and `role:<name>` (`EnsureUserRole`). They read the principal
  through an `AccountGuard` service (`Authenticatable`-typed) rather than the `User` model, again to
  keep Presentation off module internals. The feature tasks that own each write surface apply them
  per route; the scaffold registers the aliases and proves them with throwaway routes in
  `tests/Feature/Auth/WriteGatingMiddlewareTest`.
- **`has_verified_coc_account`** is derived from `verified_accounts_count > 0` until the CoC module
  lands (Phase 2); the `coc.verified` gate reads that count.
- **Minor fix in passing:** `LogRequests` fell back to `Log::channel('')` when no request channel is
  configured (the test default `LOG_REQUEST_CHANNEL=null`); it now falls back to the `null` discard
  channel. The bug only surfaced once authenticated write-gated routes exercised the terminate log.

## 4. Authentication strategy

### Mechanism
- **Session-based authentication** (Laravel's built-in guard), database session driver. Livewire
  uses the same session; no token juggling.
- No API tokens or Sanctum in the MVP — there is no public API and no separate frontend. When a
  public read API arrives (Phase 7), it will be Sanctum-issued, scoped, per-user tokens with their
  own rate limits.
- Remember-me is enabled with a rotating recaller token.

### Registration
Email + username + password + Turnstile. Disposable-domain blocklist. Email verification required
before any write. Username reserved list (`admin`, `mod`, `support`, `api`, `u`, `base`, ...).

### Password policy
- Minimum 10 characters, no composition rules (they harm more than help).
- Rejected if present in the Have-I-Been-Pwned range API (`Password::uncompromised()`), checked
  asynchronously with a fail-open-but-logged behaviour if the service is unreachable.
- Hash: bcrypt cost 12, rehash on login when the cost changes.

### Session security
- Regenerate the session id on login, logout and privilege change.
- Absolute session lifetime 30 days, idle lifetime 14 days.
- Session records store device, IP and last-active for the session-management UI.
- Password change, email change and 2FA change invalidate all other sessions.

### Rate limits (named limiters)

| Limiter | Limit | Key |
|---|---|---|
| `login` | 5 / min, then 20 / hour | ip + email |
| `register` | 3 / hour | ip |
| `password-reset` | 3 / hour | ip + email |
| `verify-email-resend` | 3 / hour | user |
| `coc-attach` | 5 / hour | user |
| `coc-refresh` | 1 / 10 min | user + account |
| `base-publish` | 5 / day, 20 / week | user |
| `comment` | 10 / hour, 60 / day | user |
| `report` | 20 / day | user |
| `upload-intent` | 30 / hour | user |
| `search` | 60 / min | ip |
| `global-write` | 120 / min | user |

All limiters are defined centrally and use the `Cache` facade so they move to Redis unchanged.

### Two-factor (Phase 2, mandatory for staff)
TOTP with 8 single-use recovery codes. Moderator+ accounts cannot be granted a staff role until 2FA
is enabled — enforced in the role-assignment service.

### Account recovery
Email-based reset only. **No security questions, no support-driven manual resets in the MVP**: a
manual reset path is the single most-abused social-engineering vector on community platforms. If a
user loses email access, their CoC accounts can be re-claimed on a new account via in-game token
verification — which is stronger proof than anything support could verify.

### Implementation notes (Phase 1 — register/login/verify/reset)

- **Built on Laravel's native auth primitives** (session guard, `PasswordBroker`, `MustVerifyEmail`,
  signed URLs), not Laravel Fortify. Native primitives gave cleaner per-route rate limiting and the
  enumeration-safe registration below; Fortify can still be layered later without changing the model.
- **Registration is enumeration-safe:** the form never reveals whether an email exists. A new email
  creates an unverified account and sends the verification link; an existing email creates nothing
  and emails the real owner instead. Both show the same "check your email" screen. Registration does
  not auto-login — the emailed verification link is signed and works signed-out.
- **HIBP** is checked synchronously with `Password::uncompromised()` (fail-open on an unreachable
  service), not on a background job; the fail-open is the spec's async intent without the queue hop.
- **Session-management columns** (`sessions.device_label`) and the handler override that fills them
  are deferred to the "Settings incl. sessions" task; the table keeps Laravel's default
  `ip_address`/`user_agent` for now so sessions work. 2FA is Phase 2.
- **Settings task:** the database session handler writes device label and creation time alongside
  Laravel's live IP/user agent. The session row keeps the IP in the clear because the owner reads it
  in the sessions UI; it is not hashed, since a hash next to its own plaintext protects nothing.
  Idle expiry is 14 days and absolute expiry is 30 days. Pending-deletion users may sign in to cancel, but all ordinary writes remain blocked.
  Password and email changes require the current password, rotate the remember token, and revoke
  other sessions. Email changes send a notice to the old address and re-verify the new address.
- **Rate limiters** `login`, `register`, `password-reset`, `verify-email-resend`, `username-check`
  are defined centrally in `AuthServiceProvider` via the `Cache` facade (Redis-swappable). The
  remaining limiters in the table below are added by the features that own them.
