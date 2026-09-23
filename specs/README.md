# CoC Community Platform — Specs

Technical plan split into loadable specs. Source brief: [project.md](project.md).

Stack: **Laravel + Livewire + PostgreSQL**, Laravel built-in (database/file) cache — **no Redis**. Modular monolith, media on Cloudflare R2 + CDN.

## Load order (roadmap)

Load in this order — each builds on the previous. For agentic coding, load `README` + the target spec + its listed dependencies.

| # | File | Load when | Depends on |
| --- | --- | --- | --- |
| 1 | [01-product-and-requirements.md](01-product-and-requirements.md) | Always first — scope, MVP, roles, features | — |
| 2 | [02-architecture-and-stack.md](02-architecture-and-stack.md) | Before any implementation | 01 |
| 3 | [03-database.md](03-database.md) | Before building any module | 01, 02 |
| 4 | [04-coc-integration-and-claiming.md](04-coc-integration-and-claiming.md) | Phase 1–2 (the integrity core) | 02, 03 |
| 5 | [05-media-storage.md](05-media-storage.md) | Before uploads (Phase 0 stub, Phase 3 full) | 02, 03 |
| 6 | [06-auth-and-security.md](06-auth-and-security.md) | Phase 0, and cross-cutting throughout | 01, 02 |
| 7 | [07-moderation.md](07-moderation.md) | Phase 4 | 03, 06 |
| 8 | [08-recruitment.md](08-recruitment.md) | Phase 5 | 03, 04 |
| 9 | [09-marketplace.md](09-marketplace.md) | Phase 7 (post-MVP) | 03, 06, 07 |
| 10 | [10-infrastructure.md](10-infrastructure.md) | Ongoing — jobs, caching, scaling | 02, 03 |
| 11 | [11-phases-risks-edgecases.md](11-phases-risks-edgecases.md) | Planning each phase | all |
| 12 | [12-design-system.md](12-design-system.md) | Any UI work (Phase 3+) — palette, fonts, tokens | 01 |
| 13 | [13-social-sharing.md](13-social-sharing.md) | Public share pages (bases/profiles) — OG previews | 03, 05, 12 |

## MVP = Phases 0–4
Verified accounts + public profiles + base sharing (images) + reports/moderation. Everything else is deferred — see [11-phases-risks-edgecases.md](11-phases-risks-edgecases.md).

## Build sequence at a glance
`0 Foundation` → `1 CoC integration` → `2 Player accounts & profiles` → `3 Bases (images)` → `4 Community & safety` → `5 Recruitment` → `6 Messaging` → `7 Marketplace (no payments)` → `8 Advanced`.
