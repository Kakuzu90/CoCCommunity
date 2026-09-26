# Game asset pack

Byte-exact Clash of Clans assets served from the `game/` bucket prefix (specs/10 §11, specs/18 §2).
**Not user media**: no `media` row, no pipeline, and never re-encoded.

- One directory per kind: `units`, `heroes`, `spells`, `pets`, `machines`, `equipments`,
  `guardians`, `townhalls`, `leagues` (mapping in `config/assets.php` → `directories`).
- `manifest.json` records, per asset: `key`, `slug`, `name`, `category` (`unit` | `townhall` |
  `league`), `kind`, `village`, `source`, `sha256`, `bytes`. `GameAssetResolver` reads it at
  runtime, and `assets:verify-pack` audits the bucket against it.
- Unit slugs are `Str::slug()` of the API name (`P.E.K.K.A` → `pekka`, `Healing Spell` →
  `healing-spell`). League emblems are one per family (`Wizard League 12` → `wizard`).

## Adding or replacing assets

1. Drop the original file in the right directory, **byte-for-byte**. Do not resize, convert,
   optimise or sprite-sheet it. `pngcrush`/`imageoptim` rewrite the file, so the asset is no
   longer unmodified. File names must be lowercase kebab-case; rename the file if needed.
2. `php artisan assets:build-manifest`. Checksums are recomputed. Slugs and names already in the
   manifest are kept, so fix a new entry's `slug`/`name` by hand if the API spells it differently.
3. `php artisan assets:publish-pack`. Uploads to `game/`, sets `Content-Type` from the real
   signature and `Cache-Control: immutable`, and checksums every object before and after upload.
4. Commit the files and the manifest, then deploy. URLs carry `?v={sha256 prefix}`, so a replaced
   file gets a new URL.
