# Clash Commons application

Laravel 12 / PHP 8.3, Livewire 3 (includes Alpine), Tailwind 4, PostgreSQL 16.

See [the working agreement](../CLAUDE.md), [Docker setup](../DOCKER.md), and
[the specification index](../specs/README.md).

Run `composer ci` for formatting, static analysis, dependency boundaries, and Pest.
Run `npm ci && npm run build` with Node 22 for frontend validation.
The GitHub Actions matrix also runs the tests against PostgreSQL.
