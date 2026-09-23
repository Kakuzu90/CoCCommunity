# 10 — Infrastructure (Jobs, Caching, Scaling)

## 22. Background jobs & scheduled tasks

Queue driver: **database** (no Redis). Workers run via `queue:work`; Scheduler runs one system cron.

**Queued jobs (event-driven):**
- `VerifyCocToken`, `SyncCocAccount`, `SyncClan` — API calls off the request path.
- `ProcessUploadedMedia` (validate, scan, thumbnail), `TranscodeVideo` (post-MVP).
- `SendNotification` (in-app fan-out; email later), `RecomputeBaseCounters`.
- `DetectDuplicateBase` (image hashing) on publish.

**Scheduled tasks:**
| Task | Cadence | Purpose |
| --- | --- | --- |
| Refresh verified accounts | every 6–12 h, staggered | keep snapshots current within API limits |
| Refresh clan data | daily | clan levels, membership |
| Recompute trending | hourly | trending bases ranking |
| Reap orphaned media | hourly | delete pending/unreferenced R2 objects |
| Prune stale sessions/tokens | daily | hygiene |
| Retry failed syncs | every 30 min | drain failed-job backlog |

- **Queue health:** monitor `failed_jobs`; alert on depth/age. Queues sit in Postgres — watch table bloat and vacuum.
- **Backoff:** external-API jobs use exponential backoff + max attempts, then dead-letter for manual review.

## 23. Caching strategy (Laravel built-in, no Redis)

Use Laravel's cache abstraction with the **database** (or **file**) driver — same `Cache` API as Redis, so swapping later is a config change.

- **What to cache:**
  - CoC snapshot reads — cache the assembled profile view.
  - Trending/popular base lists — recomputed by a job, cached for the hour.
  - Expensive aggregates (profile stats, counts) — short TTL, invalidate on write via events.
  - Config/reference data (categories, tags) — long TTL.
  - CoC API responses — brief TTL to collapse duplicate lookups.
- **Invalidation:** event-driven (`BaseLiked` → bust that base's cache); tag-able caches per entity where supported; else versioned cache keys.
- **Counters:** maintain `like_count`/`view_count` columns updated by jobs rather than caching `count(*)`; buffer views and flush periodically.
- **Rate limiting:** Laravel's rate limiter on the database/cache store.
- **Limits to accept:** database cache adds load to Postgres and lacks Redis's atomic primitives (locks, sorted sets). Fine at MVP; trigger to adopt Redis is measured contention/lock needs.

## 24. Scaling considerations

- **Web tier:** stateless → add app instances behind a load balancer; sessions in DB/cookie.
- **Database first bottleneck:** it's also cache + queue store. Scale by read replicas, connection pooling (PgBouncer), indexing discipline, then move cache/queue to Redis when Postgres write load from those becomes the constraint.
- **Queues:** scale workers horizontally; partition by queue (sync vs media vs notifications) so slow media jobs don't starve verification.
- **Media:** already on R2 + CDN — scales independently.
- **Search:** DB full-text → dedicated engine (Meilisearch/Typesense/Elastic) behind the same search interface.
- **CoC API:** the true hard ceiling — staggered syncs, longer TTLs, prioritize active users; more keys/IPs only within Supercell's terms.
- **Sequence of moves:** indexes + caching → read replica → Redis for cache/queue → dedicated search → extract a hot module to its own service only if a domain clearly outgrows the monolith.
