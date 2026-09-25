# Game asset packs

Byte-exact, versioned Clash of Clans assets served from the `game/{version}/` bucket prefix
(specs/10 §11, specs/18 §2). **Not user media** — no `media` row, no pipeline, no re-encode, ever.

- One directory per pack version: `resources/game-assets/{version}/`.
- Each carries a `manifest.json` recording, per asset: `key`, `slug`, `name`, `category`
  (`unit` | `townhall` | `league`), `village`, `source`, `sha256`, `bytes`.
- The committed manifest is what `GameAssetResolver` reads at runtime (no per-request bucket
  listing). The bucket copy is what `assets:verify-pack` audits against.

## Publishing a pack (runbook)

1. Assemble the files locally, **byte-for-byte** — no resizing, format conversion, optimisation or
   sprite-sheeting. `pngcrush`/`imageoptim` are out: they rewrite the file and forfeit "unmodified".
2. Generate `manifest.json` (key/slug/name/category/village/source/sha256/bytes per asset).
3. `php artisan assets:publish-pack {path} --pack={n}` — uploads to `game/{n}/`, sets
   `Content-Type` from the real signature and `Cache-Control: immutable`, and verifies every
   object's checksum. It aborts on any mismatch; a partial pack is never activated.
4. Commit the manifest here **and** keep the copy that shipped in the bucket.
5. Activate with `config('assets.pack_version')` (env `GAME_ASSET_PACK_VERSION`). Rollback is a
   one-line revert — the previous version stays in the bucket.

Version 1 is a placeholder release. Its manifest has no game files, so unit, Town Hall and league
lookups use the labelled fallback in `<x-game.asset>`. It must not be published as a game asset pack.
When curated originals are available, assemble a new numbered pack and activate that version.
