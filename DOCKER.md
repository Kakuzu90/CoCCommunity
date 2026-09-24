# Docker — local dev

The Laravel app lives in [`src/`](src/). Stack: PHP 8.3-FPM, Nginx, PostgreSQL 16, a queue worker, a scheduler, MinIO and Mailpit. **No Redis** — cache, queue, and sessions use the database driver (see [`specs/21-caching-strategy.md`](specs/21-caching-strategy.md)).

Everything runs locally with no external accounts: MinIO stands in for Cloudflare R2, Mailpit for the mail provider, and the Clash of Clans client binds to a recorded-fixture fake. Moving to the real services is an `.env` change (see [`specs/10-media-storage.md` §2.1](specs/10-media-storage.md)).

## Services

| Service | Purpose | Local URL / port |
| --- | --- | --- |
| `web` | Nginx → PHP-FPM | http://localhost:8080 |
| `app` | PHP-FPM (Laravel) | — |
| `queue` | `queue:work database` | — |
| `scheduler` | `schedule:run` each minute | — |
| `db` | PostgreSQL 16 | localhost:5432 |
| `minio` | S3-compatible object storage (stands in for R2), profile `storage` | API localhost:9000 · console http://localhost:9001 |
| `minio-init` | One-shot: creates the bucket, opens `public/` and `game/`, profile `storage` | — |
| `mailpit` | Catches outgoing mail | http://localhost:8025 |
| `adminer` | DB GUI (Postgres, like phpMyAdmin), profile `tools` | http://localhost:8081 |
| `node` | Vite dev server (profile `assets`) | localhost:5173 |

## First run

```bash
# 1. Create the Laravel app into src/ (only if it doesn't exist yet)
docker compose run --rm app composer create-project laravel/laravel .

# 2. Configure env: start from Laravel's own template, then append the Docker keys
#    (the Docker file is a partial — it does not contain APP_KEY, APP_ENV, etc.)
cp src/.env.example src/.env
cat .env.docker.example >> src/.env        # later keys win; review the result once
docker compose run --rm app php artisan key:generate

# 3. Start the stack. Add --profile storage whenever you are doing media work
#    (minio-init then creates the bucket and exits).
docker compose --profile storage up -d --build

# 4. Migrate (and create the cache/queue/session tables)
docker compose exec app php artisan migrate
docker compose exec app php artisan session:table
docker compose exec app php artisan queue:table
docker compose exec app php artisan cache:table
docker compose exec app php artisan migrate

# 5. Check storage is reachable (needs --profile storage running; empty list is fine)
docker compose exec app php artisan tinker --execute="dump(Storage::disk('s3')->files());"
```

## Everyday commands

```bash
docker compose up -d                       # start
docker compose logs -f app                 # tail logs
docker compose exec app php artisan test   # run Pest
docker compose exec app php artisan tinker
docker compose --profile assets up node    # Vite dev server for frontend work
docker compose --profile tools up -d adminer  # DB GUI at http://localhost:8081 (server: db / coc / secret)
docker compose --profile storage up -d        # MinIO, console http://localhost:9001 (minioadmin / minioadmin)
docker compose down                        # stop (add -v to drop the DB volume)
```

Ports are overridable via `APP_PORT`, `DB_PORT`, `MAILPIT_UI_PORT`, `VITE_PORT`, `MINIO_PORT`, `MINIO_CONSOLE_PORT` in your shell or an `.env` beside `docker-compose.yml`.

## Project state

`src/` is empty. No application code has been written yet — the previous implementation was removed
in the spec refactor. Start from `CLAUDE.md` and `specs/25-development-phases.md`; the first task is
Phase 0 "Project setup".

Notes for whoever writes the first migrations: roles are a single `users.role` enum column, **not**
Spatie's permission tables, and domain code lives in `app/Domain/*` (see
[`specs/19-module-structure.md`](specs/19-module-structure.md)).

## Environment (`src/.env`)

`src/.env` is git-ignored, so it does **not** travel with the repo. On a fresh checkout you recreate it; `.env.docker.example` is the reference for the Docker-specific keys.

**Already wired** (set during scaffolding — no action needed for local dev):

**Recreating `src/.env` from scratch:**

```bash
cp src/.env.example src/.env            # Laravel's own template
docker compose run --rm app php artisan key:generate
# then re-apply the Docker keys from the "Already wired" table above
# (or copy the relevant lines out of .env.docker.example)
```
