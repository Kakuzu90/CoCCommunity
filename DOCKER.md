# Docker — local dev

The Laravel app lives in [`src/`](src/). Stack: PHP 8.3-FPM, Nginx, PostgreSQL 16, a queue worker, a scheduler, and Mailpit. **No Redis** — cache, queue, and sessions use the database driver (see [`specs/10-infrastructure.md`](specs/10-infrastructure.md)). Media is on Cloudflare R2 (external).

## Services

| Service | Purpose | Local URL / port |
| --- | --- | --- |
| `web` | Nginx → PHP-FPM | http://localhost:8080 |
| `app` | PHP-FPM (Laravel) | — |
| `queue` | `queue:work database` | — |
| `scheduler` | `schedule:run` each minute | — |
| `db` | PostgreSQL 16 | localhost:5432 |
| `mailpit` | Catches outgoing mail | http://localhost:8025 |
| `adminer` | DB GUI (Postgres, like phpMyAdmin), profile `tools` | http://localhost:8081 |
| `node` | Vite dev server (profile `assets`) | localhost:5173 |

## First run

```bash
# 1. Create the Laravel app into src/ (only if it doesn't exist yet)
docker compose run --rm app composer create-project laravel/laravel .

# 2. Configure env (merge the docker keys into src/.env)
cp .env.docker.example src/.env   # then set app key below
docker compose run --rm app php artisan key:generate

# 3. Start the stack
docker compose up -d --build

# 4. Migrate (and create the cache/queue/session tables)
docker compose exec app php artisan migrate
docker compose exec app php artisan session:table
docker compose exec app php artisan queue:table
docker compose exec app php artisan cache:table
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
docker compose down                        # stop (add -v to drop the DB volume)
```

Ports are overridable via `APP_PORT`, `DB_PORT`, `MAILPIT_UI_PORT`, `VITE_PORT` in your shell or an `.env` beside `docker-compose.yml`.

## Foundation migrations

Run `docker compose exec app php artisan migrate` after updating this checkout. Module migrations are loaded from `src/app/Modules/*/migrations`. They install Spatie's permission tables, seed the four platform roles, enable user soft deletion, and create the media metadata table. Registration assigns the User role; migrations never grant staff access to an account.

Email verification is enforced on the dashboard. Open verification messages in Mailpit at http://localhost:8025. Verification resends are limited to six per minute per account; registration attempts to five per minute per IP; Livewire updates to sixty per minute per user/IP.

This implements the Phase 0 foundation plus Phase 1 (CoC integration & account claiming). Public player profiles, base sharing, media uploads/processing, moderation tools, and staff role-management endpoints are not implemented yet. The media table stores metadata only and defaults to `pending`; no upload endpoint exposes unprocessed files.

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
