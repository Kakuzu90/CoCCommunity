# 13 — Social Sharing (Open Graph link previews)

When a user copies a link to a base, profile, or recruitment post and pastes it into Facebook, X, Discord, WhatsApp, etc., that platform fetches the page and renders a rich card from its Open Graph tags. This is the "share a link that shows data" feature. It also advances the social-profile goal ([`specs/01`](01-product-and-requirements.md) §3) and copy-link analytics ([`specs/01`](01-product-and-requirements.md) §4).

## What crawlers read

Livewire is server-rendered, so the meta tags sit in the initial HTML — no SPA/JS-injection problem. Each shareable page outputs, in the Blade layout `<head>`:

```
<meta property="og:type"        content="website">
<meta property="og:title"       content="TH17 Anti-3 — Ring base by NightWitch">
<meta property="og:description" content="Legend-league war base · 1.2k likes · Verified creator">
<meta property="og:image"       content="https://cdn.clashcommons.gg/og/base/1234.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url"         content="https://clashcommons.gg/b/1234">
<meta name="twitter:card"       content="summary_large_image">
```

Provide a per-page override mechanism (e.g. a `@section('meta')` or a shared `<x-og>` component) so each controller/Livewire page sets its own title/description/image; the layout renders sensible site defaults when a page sets none.

## Shareable entities & canonical URLs

| Entity | Canonical share URL | Card shows |
| --- | --- | --- |
| Base layout | `/b/{id}` (or slug) | screenshot, title, TH + category, likes, author |
| Player profile | `/u/{username}` | avatar, verified badge, featured account TH/trophies, base count |
| Recruitment post | `/r/{id}` | clan/LFC summary, required TH, league, language |
| Marketplace listing (later) | `/m/{id}` | title, price, seller rating |

Each shareable model exposes a canonical URL and the four card fields (title, description, image URL, type) through a small `Shareable` contract or a presenter — do not scatter OG string-building across views.

## The OG image ("displays some data")

- **MVP:** use the entity's primary screenshot/avatar directly as `og:image`. Immediate, no generation.
- **Enhanced (Phase 7-ish):** generate a **composed card** (1200×630) per entity on the Dark Elixir Royal palette ([`specs/12`](12-design-system.md)) — screenshot + TH badge + category + likes/trophies + author. Render server-side in a queued job, store as a `media` derivative, serve from CDN, and **regenerate when the underlying data changes** (new like milestone, edited title). Treat it like any other derivative in the media pipeline ([`specs/05`](05-media-storage.md)).
- Requirements: absolute CDN URL, 1200×630, under ~5MB, cached.

## Privacy gating (hard rule)

- Only `public` bases/profiles/posts get OG tags **and** a crawlable public page. `unlisted` and `private` must render **no** OG data and must not expose stats in a preview — enforced centrally (same rule as [`specs/19` edge cases](11-phases-risks-edgecases.md): "privacy vs discovery"), never per-page.
- A suspended/removed entity's share page returns a neutral 404/410 with no data, and its cached OG image is purged.

## Caching

- Crawlers and re-shares hit these URLs heavily. Cache the assembled meta and the generated OG image (DB/file cache + CDN, per [`specs/10`](10-infrastructure.md)).
- Bust the cache when the entity's title/stats/visibility change.

## Video caveat

`og:video` / player cards are unreliable on Facebook and X. For replay videos, the shared card shows the **image + link**; clicking opens the replay on-site. Do not promise inline autoplay of replays in a social feed.

## UI

- A **"Copy share link"** action on bases, profiles, and recruitment posts. Copies the canonical URL; falls back to selecting the text if the clipboard API is refused (see the `livewire-ui` skill).
- Track copy-link clicks and inbound referral traffic for the `copy-base clicks` / analytics features.

## Scope

- **MVP:** OG tags on public base + profile pages, screenshot/avatar as the image, `Copy share link` button.
- **Later:** dynamic composed OG card images, recruitment + marketplace cards, share analytics.
