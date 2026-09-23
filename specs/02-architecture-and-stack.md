# 02 — Architecture & Stack

## 7. Recommended architecture

**Modular monolith.** One deployable Laravel app, internally split into bounded modules that talk through service classes and events, not by reaching into each other's models.

- **Web tier:** stateless Laravel app, horizontally scalable behind a load balancer.
- **Async tier:** queue workers (database driver) for CoC syncs, media processing, notifications.
- **Scheduler:** Laravel Scheduler (one cron) for snapshot refresh, cleanup, trending recompute.
- **Data:** PostgreSQL (primary), Cloudflare R2 for media, CDN in front.
- **External:** official CoC API behind an anti-corruption service layer (see `04`).
- **Boundary rule:** cross-module communication via published domain events (`BaseLiked`, `AccountVerified`) and thin service interfaces; a module owns its tables and exposes read models, not raw Eloquent.

## 8. Recommended Laravel & frontend stack

**Verdict: Laravel + Livewire + PostgreSQL, no Redis.**

| Option | Fit |
| --- | --- |
| **Laravel + Livewire (chosen)** | Server-rendered, reactive where needed, no API layer to build twice. Best velocity for a content/CRUD platform. |
| Laravel + Inertia + Vue/React | Only if a rich SPA or near-term public/mobile API is a goal. Adds JS build + client-state overhead. |
| Separate API + SPA | Overkill for MVP; defer until a mobile app is committed. |

**Stack:**
- **PostgreSQL** over MySQL — native JSONB for CoC snapshot blobs and strong full-text search for the DB-first search phase.
- **Cache & queue: Laravel built-in drivers, no Redis.** Use `database` driver for queues and `database`/`file` cache. Config-swappable to Redis later (one-line change).
- **Object storage:** Cloudflare R2 (S3-compatible, zero egress) + CDN.
- **Media processing:** queued workers (Intervention Image; ffmpeg for video, post-MVP).
- **Auth:** Laravel Fortify/Breeze; Spatie laravel-permission for RBAC.
- **Trade-off of dropping Redis:** no sub-ms cache; DB-backed queues add write load. Acceptable at MVP; adopt Redis only when contention/throughput demands.

## 21. Laravel module & folder structure

Modules under `app/Modules/<Domain>`, each self-contained. Cross-module calls via service interfaces + events only.

```
app/
  Modules/
    Auth/
    Users/            # profiles, privacy, avatars
    CocIntegration/   # ClashClient, adapters, DTOs, sync jobs
    PlayerAccounts/   # coc_accounts, claims, snapshots, verification
    Clans/            # clans, memberships
    Bases/            # layouts, tags, likes, bookmarks, trending
    Recruitment/      # posts, applications
    Marketplace/      # listings, orders, reviews, disputes
    Messaging/        # conversations, messages
    Media/            # upload URLs, validation, thumbnails, cleanup
    Notifications/     # in-app center, channels
    Moderation/       # reports, actions, queues
    Admin/            # RBAC panel, dashboards
  Support/            # shared value objects, base classes
Each module:
    Models/  Services/  Policies/  Http/ (Livewire+Controllers)
    Jobs/  Events/  Listeners/  Actions/  routes.php  migrations/
```

- **Boundaries:** a module owns its tables/migrations; others read via its Service/read-model.
- **Events over calls:** `AccountVerified`, `BaseLiked`, `ReportFiled` decouple side effects.
- **Enforcement:** an architecture test (deptrac/pest) fails the build on illegal cross-module references.
