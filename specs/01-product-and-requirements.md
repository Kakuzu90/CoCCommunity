# 01 — Product & Requirements

## 1. Product overview

A web platform where Clash of Clans players link multiple in-game accounts (verified by player tag), build public profiles, share base layouts with screenshots and replay links, recruit or find clans, and buy/sell permitted community services. It is a trust-and-content platform, not a game utility: the hard problems are **verified account ownership** and **content moderation at scale**.

- **Primary users:** individual players and clan leaders/recruiters.
- **Secondary users:** service sellers (marketplace) and moderators/admins.
- **Value:** a single verified identity across a player's CoC accounts, plus discovery (bases, clans, players) the official app does not offer.
- **Non-goals (hard):** no buying/selling/transferring CoC *accounts*; no automation that violates Supercell's ToS; no storing media in the relational database.

## 2. MVP definition

The MVP proves **verified CoC account ownership**, then hangs base sharing off it:

1. Website auth (register, login, email verify, password reset, profile + avatar).
2. Link CoC accounts by tag, verified via the official **API token endpoint**. Store periodic snapshots.
3. Public player profile showing verified accounts + their bases.
4. Base layouts: title, description, TH level, category, tags, base link, **images only (no video)**, likes + bookmarks + views.
5. Basic search/filter over bases and accounts (database-backed).
6. Reports + minimal admin panel (users, accounts, bases, reports, suspensions, audit log).

**Deferred from MVP:** marketplace, payments/escrow, video, messaging, recruitment, following, achievements, dedicated search engine, email notifications. See `11-phases-risks-edgecases.md`.

## 3. Functional requirements

| Domain | Must support |
| --- | --- |
| Accounts | Register, login/logout, email verify, password reset, edit profile, avatar, privacy settings, soft-delete |
| CoC linking | Add by tag, token verification, snapshot sync, states (unverified/verified/disputed/suspended), soft cap, per-account images |
| Profiles | Username, avatar, bio, verified accounts, featured account, bases, verified badge, stats |
| Bases | CRUD, images, category, tags, base link, likes, bookmarks, views, copy-clicks, reports, trending |
| Recruitment | Player LFC profiles; clan recruitment posts; apply / show interest |
| Marketplace | Seller profiles, listings, orders, status, reviews, messaging, disputes (post-MVP) |
| Moderation | Report any entity, reason + evidence, moderator queue, decisions, suspensions/bans, audit trail |
| Notifications | In-app for verification, disputes, likes, comments, applications, orders, admin decisions; email optional |
| Search | Filter players, accounts, bases, clans, recruitment, listings; DB-backed first |
| Admin | Manage all entities, RBAC (User/Moderator/Admin/Super Admin), moderation + audit logs |

## 4. Non-functional requirements

- **Performance:** profile/base-list pages < 300 ms server time at MVP scale; heavy reads cached. CoC API never called synchronously in a request path.
- **Availability:** single-region, target 99.5% at MVP; degrade gracefully when CoC API is down (serve stale snapshots, flag as stale).
- **Scalability:** stateless web tier; media on object storage + CDN; search swappable DB → dedicated engine without touching callers.
- **Cost:** one app server, one managed Postgres, R2 (zero egress), CDN. **No Redis**; cache/queue use the database driver.
- **Security & privacy:** first-class; privacy settings honored everywhere; audit log for every sensitive mutation.
- **Maintainability:** clear module boundaries, policy-based authorization, no business logic in controllers.
- **UX:** responsive, mobile-first.

## 5. User roles & permissions

Four roles, RBAC via policy layer. Guests read public content only.

| Capability | User | Moderator | Admin | Super Admin |
| --- | --- | --- | --- | --- |
| Manage own profile, accounts, bases | Yes | Yes | Yes | Yes |
| Report content | Yes | Yes | Yes | Yes |
| Review reports, hide content, warn | No | Yes | Yes | Yes |
| Resolve ownership disputes | No | Yes | Yes | Yes |
| Suspend / ban users | No | Limited (temp) | Yes | Yes |
| Manage marketplace listings/orders | No | Yes | Yes | Yes |
| Manage roles & permissions | No | No | Up to Moderator | Any |
| Manage system settings / feature flags | No | No | No | Yes |
| View audit logs | No | Own actions | Yes | Yes |

- All user-owned mutations checked by policy (`can:update,base`), never route guards alone.
- **Super Admin** is break-glass: tiny fixed set of people, all actions audit-logged.

## 6. Complete feature breakdown

- **Auth & account:** email/password, verification, reset, sessions, 2FA (post-MVP), privacy, avatar, soft delete + export.
- **CoC accounts:** add by tag, token verification, snapshot sync (manual + scheduled), states, featured account, per-account images (cap 5, 5 MB each), unlink.
- **Profiles:** public page, verified badge, stats rollup, followers/following (post-MVP), activity feed (post-MVP).
- **Bases:** CRUD, categories (War, CWL, Farming, Trophy, Legend, Anti-3/2-Star, Hybrid, Progress, Troll), tags, base link, 2 images + 1 video (video post-MVP), likes, bookmarks, views, copy-clicks, trending, reports.
- **Recruitment:** LFC profiles, clan posts, apply/interest, recruiter inbox.
- **Marketplace:** seller profiles, listings, orders, status, reviews, messaging, disputes, optional escrow (all post-MVP).
- **Messaging:** conversations + messages (recruitment/marketplace scoped first).
- **Notifications:** in-app center + read state; email opt-in.
- **Moderation & admin:** report intake, queue, decisions, suspensions/bans, audit + moderation logs, RBAC panel.
- **Search & discovery:** filtered lists, trending, saved searches (post-MVP).
