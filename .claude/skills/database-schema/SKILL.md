---
name: database-schema
description: Write migrations and schema changes for this PostgreSQL app. Use when adding or altering tables, columns, indexes, or constraints. Enforces the schema in specs/03, JSONB for snapshot data, and the no-media-in-DB rule.
---

# Database schema

PostgreSQL. The authoritative schema is [`specs/03-database.md`](../../../specs/03-database.md) — match its table names, columns, relationships, unique constraints, and indexes. Deviating from it is a spec change; flag it.

## Conventions

- Migrations live in the owning module's `migrations/` folder (see the `laravel-module` skill).
- Every table: `id`, `created_at`, `updated_at`. Add `deleted_at` (soft delete) for user-facing content (users, profiles, bases, comments, listings, messages).
- **JSONB, not child tables, for API snapshot blobs.** Heroes/troops/spells/league detail live in `coc_account_snapshots.data` (JSONB). Do not normalize them.
- **No media bytes in the DB.** Only a `media` row (disk, path, mime, size, checksum, status, uploader_id) pointing at R2. See [`specs/05-media-storage.md`](../../../specs/05-media-storage.md).
- Backed PHP enums map to string/enum columns for states (account state, report status, order status). Keep DB values and the enum in sync.
- Money and counts are integers. Counters (`like_count`, `view_count`) are columns updated by events/jobs.
- Foreign keys with explicit `on delete` behavior (usually `cascade` for children, `restrict`/`set null` where history must survive — decide per the edge cases in [`specs/11-phases-risks-edgecases.md`](../../../specs/11-phases-risks-edgecases.md)).

## Critical constraints (do not omit)

- `coc_accounts.tag` — **unique** (one verified owner per tag).
- `base_likes` — **unique(base_layout_id, user_id)**.
- `base_bookmarks` — unique(base_layout_id, user_id).
- `recruitment_applications` — unique(recruitment_post_id, applicant_user_id).
- `marketplace_reviews` — unique(order_id, rater_id).
- `profiles` — unique(user_id), unique(username).

## Indexing

Add the indexes listed per table in `specs/03`. Prioritize the columns that filter list/discovery pages:
- `base_layouts(th_level, category, visibility)` and `(user_id)`.
- `recruitment_posts(type, status, language, location)`.
- polymorphic `(reportable_type, reportable_id)`, `(subject_type, subject_id)`.
- time-ordered reads: `coc_account_snapshots(coc_account_id, fetched_at desc)`.

Don't add speculative indexes. Add one when a query needs it; note why in the migration.

## Steps

1. Check `specs/03` for the exact table/column/index definition. Reuse those names.
2. Write the `up` and a real `down` (reversible). No empty `down`.
3. Add the unique/foreign-key constraints in the same migration as the table.
4. Update the Eloquent model: `$fillable`, casts (enum, JSONB→array, immutable dates), relationships.
5. Add/adjust a factory and seeder if the table is used in tests.
6. Run the migration against a scratch DB and the relevant tests before finishing.

## Do not

- Do not store files, base64 blobs, or large text dumps in a column — use `media`.
- Do not skip the `down` migration.
- Do not rename or drop a spec'd column/constraint without changing the spec and saying why.
