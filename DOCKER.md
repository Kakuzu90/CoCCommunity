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
docker compose down                        # stop (add -v to drop the DB volume)
```

Ports are overridable via `APP_PORT`, `DB_PORT`, `MAILPIT_UI_PORT`, `VITE_PORT` in your shell or an `.env` beside `docker-compose.yml`.
