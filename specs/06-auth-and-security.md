# 06 — Auth & Security

## 13. Authentication & authorization strategy

- **Authentication:** Laravel Fortify/Breeze — hashed passwords (bcrypt/argon2), email verification required before linking accounts, signed reset links, throttled login, optional 2FA (post-MVP).
- **Sessions:** server-side, secure + http-only cookies, session-fixation protection, regenerate on login, logout invalidates.
- **Authorization:** **policy-first**. Every model has a Policy; controllers/Livewire components call `authorize()`/`can`. No authorization logic in views or route closures.
- **RBAC:** Spatie laravel-permission for roles (User/Moderator/Admin/Super Admin) and granular permissions, layered on ownership policies.
- **Ownership vs role:** a User edits only their own resources (policy by `user_id`); Moderators/Admins get elevated policies via permissions — the two compose, IDOR closed by always checking ownership.
- **Admin surface:** separate route group + middleware requiring an admin permission; Super Admin actions double-gated and audit-logged.
- **API tokens (future):** Sanctum when a mobile app or public API arrives.

## 14. Security requirements

| Threat | Control |
| --- | --- |
| SQL injection | Eloquent/query builder only, parameter binding; no raw string SQL |
| XSS | Blade auto-escaping; sanitize rich text; CSP header; escape user content in Livewire |
| CSRF | Laravel CSRF tokens on all state-changing requests |
| IDOR | Policy check on every resource by ownership; never trust request IDs |
| Broken authz | Policy-first, deny by default, authorization tests |
| File upload attacks | Signature + MIME check, re-encode, size/count limits, malware scan, cookieless media domain |
| Mass assignment | Explicit `$fillable` / form-request validation; never `$request->all()` into models |
| API abuse / scraping | Rate limits per route + per user/IP, pagination caps, no bulk export, bot detection |
| Brute force | Login throttling, exponential backoff, captcha after N failures |
| Account enumeration | Uniform responses on login/reset/register |
| Session attacks | Secure/http-only cookies, regenerate on login, idle + absolute timeouts |
| Bot registration | Email verification, captcha, disposable-email block, rate limits |
| Privilege escalation | Role changes audit-logged, Super Admin gated, no client-controlled role fields |
| Improper admin access | Separate middleware + permission, optional IP allowlist, full audit trail |
| Marketplace scams | Verified sellers, reviews, dispute flow, no on-platform payments at MVP (see `09`) |

Cross-cutting: encrypt secrets and the stored CoC API token at rest, HTTPS everywhere, dependency scanning, audit log on every sensitive mutation, least-privilege DB user.
