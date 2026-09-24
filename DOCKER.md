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
# 1. Install the committed dependencies
docker compose run --rm app composer install --no-interaction --prefer-dist

# 2. Configure the app (only on a fresh checkout)
cp src/.env.example src/.env
docker compose run --rm app php artisan key:generate

# 3. Build assets and start the stack
docker compose --profile assets run --rm node sh -c 'npm ci && npm run build'
docker compose up -d --build

# 4. Migrate. The committed migrations already include cache, queue and sessions.
docker compose exec app php artisan migrate
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

Laravel 12 is installed with Livewire 3, Tailwind 4, Pest 3, Pint, Larastan and Deptrac.
The stock User model lives in `app/Domain/Auth/Models`; its schema is still Laravel's
bootstrap schema. Identity fields and behavior belong to Phase 1.

Run the CI checks locally:

```bash
docker compose exec app composer ci
# Use a separate test database, never the development database:
docker compose exec db createdb -U coc coc_test
docker compose exec -e DB_CONNECTION=pgsql -e DB_DATABASE=coc_test app php artisan test --compact
```

`composer ci` runs Pint, PHPStan L6, PHPStan L8 on Domain, Deptrac and Pest.
GitHub Actions runs these checks plus the frontend build on SQLite and PostgreSQL 16.
Tests default to in-memory SQLite, array cache/session and synchronous queues.
Environment variables can select PostgreSQL without changing `phpunit.xml`.
Node 22 is used for the Vite 7 build. Livewire supplies Alpine; do not load it twice.

Deptrac uses the module boundaries in spec 19. Any reviewed exception belongs in
`src/deptrac.allowlist` with a reason comment. Models are internal to their module.

## Environment (`src/.env`)

`src/.env` is git-ignored, so it does **not** travel with the repo. On a fresh checkout you recreate it; `.env.docker.example` is the reference for the Docker-specific keys.

The template configures PostgreSQL, database cache/queue/sessions and Mailpit.
For media work, start `--profile storage` and apply the storage keys from
`.env.docker.example`; the media pipeline is a separate Phase 0 task.
