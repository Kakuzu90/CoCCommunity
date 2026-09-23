# 11 — Phases, Edge Cases, Risks

## 20. Development phases

Comments/notifications/reports move earlier than the original brief — bases need moderation and feedback the moment they exist.

| Phase | Scope | Exit criteria |
| --- | --- | --- |
| **0. Foundation** | Skeleton, modules, CI, auth (Fortify/Breeze), RBAC, policies, media pipeline stub | Register, verify email, log in; roles enforced |
| **1. CoC integration** | `ClashClient` adapter, token verification, snapshots, scheduled sync | User links + verifies a real account; snapshot shows |
| **2. Player accounts & profiles** | Multiple accounts, states, featured account, public profile, account images | Verified accounts render on a public profile |
| **3. Bases (images)** | Base CRUD, categories, tags, images, likes, bookmarks, views, trending | Users publish and discover bases |
| **4. Community & safety** | Comments, in-app notifications, reports, moderation queue, admin panel | Content reported and moderated end-to-end |
| **5. Recruitment** | LFC + clan posts, applications, recruiter inbox | Player applies; recruiter accepts |
| **6. Messaging** | Scoped conversations for recruitment/marketplace | Two users message within a scope |
| **7. Marketplace (no payments)** | Seller profiles, listings, orders, reviews | Listings live with reviews, no funds moved |
| **8. Advanced** | Video upload, search-engine migration, following, achievements, analytics, email notifications, Stripe Connect | Feature-flagged rollouts |

**Phases 0–4 = MVP.** Each phase independently shippable behind feature flags.

## 19. Major edge cases

- **Tag ownership churn:** account sold/traded → re-verification transfers ownership; old bases reassigned, hidden, or kept per policy.
- **Tag becomes invalid:** player deletes account or Supercell purges tag → flag `needs_reverify`, keep data, stop syncing.
- **API outage / rate limit:** serve stale snapshots flagged as such; queue backs up gracefully; manual refresh throttled.
- **Duplicate bases:** same layout reposted → perceptual image hashing flags near-duplicates for moderation.
- **Deleted user with content:** soft-delete cascade — decide whether bases survive anonymized or are removed; orphaned media reaped.
- **Concurrent verification:** two users submit tokens for one tag → last valid token wins under a DB lock; both audited.
- **Media finalize failure:** upload to R2 succeeds but finalize fails → `pending` media reaped by TTL; user retries.
- **Privacy vs discovery:** a private profile excluded from search, trending, and recruiter views everywhere — enforced centrally.
- **Report brigading:** mass reports on one target → rate-limit + de-duplicate, surface once, never auto-action.
- **Marketplace non-delivery / scam:** dispute path, seller reputation, provider-side chargeback handling (with payments).
- **Snapshot staleness:** always show `last_synced_at` so users know data isn't live.

## 25. Risks & assumptions

**Assumptions:**
- Supercell's public API remains available and `verifytoken` stays the ownership-proof mechanism.
- Outbound traffic can use a fixed IP (or proxy) so IP-bound keys work.
- Base sharing = metadata + screenshots + in-game share link; platform does not reconstruct/host playable layouts.
- Small team; velocity and low ops overhead matter more than premature scale.

**Risks:**
| Risk | Impact | Mitigation |
| --- | --- | --- |
| API terms/rate changes | Core features break | Adapter layer isolates it; cache-first; degrade to stale |
| Ownership disputes gameable | Trust erosion | Token-control-wins rule + audited transfers + admin review |
| Marketplace payments = regulatory exposure | Legal/financial | No custody; Stripe Connect only; escrow last or never |
| Postgres as cache+queue+DB | Perf ceiling | Config-swappable to Redis; monitor early |
| Malicious uploads | Security incident | Signature check, re-encode, scan, cookieless CDN |
| Moderation load > team | Abuse/spam | Auto-flag heuristics, rate limits, dedupe, triage |
| Scraping of player/base data | IP/abuse | Rate limits, pagination caps, bot detection |
| Legal: hosting user media | Takedown/DMCA | Clear ToS, report + takedown flow, audit logs |

## 26. Features NOT in the MVP

Cut from v1:
- **Marketplace payments & escrow** — regulatory exposure; even listings/messaging is post-MVP. Defer escrow indefinitely.
- **Video upload & transcoding** — start images-only.
- **Messaging/DMs** — defer until recruitment/marketplace need it.
- **Following / social graph / activity feed.**
- **Achievements & badges** (beyond verified badge).
- **Dedicated search engine** — DB full-text is enough at launch.
- **Email notifications** — in-app only first.
- **2FA, public API, mobile app, Sanctum tokens.**
- **Real-time (websockets/broadcasting)** — polling/refresh is fine.
- **Recommendations & analytics dashboards.**

**True MVP = verified accounts + public profiles + base sharing (images) + reports/moderation.**
