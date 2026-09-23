---
name: laravel-module
description: Create or extend a bounded module in this modular-monolith Laravel app. Use when adding a domain (bases, recruitment, marketplace, etc.), moving logic between modules, or wiring cross-module side effects. Enforces module boundaries and the event-over-direct-call rule.
---

# Laravel module

This app is a modular monolith. Modules live under `app/Modules/<Domain>/`. The goal of a module is a clean boundary: it owns its data and exposes intent, not internals.

Reference: [`specs/02-architecture-and-stack.md`](../../../specs/02-architecture-and-stack.md).

## Module layout

```
app/Modules/<Domain>/
  Models/        # Eloquent models — owned by this module only
  Services/      # public entry points other modules may call
  Actions/       # single-purpose write operations (one public method)
  Policies/      # authorization, one per model
  Http/          # Livewire components + controllers (thin)
  Jobs/          # queued work
  Events/        # domain events this module publishes
  Listeners/     # reactions to other modules' events
  routes.php
  migrations/
```

## Rules

1. **A module owns its tables.** Migrations for a table live in the module that owns it. No other module writes to those tables.
2. **No cross-module model imports.** If module B needs data from A, it calls `A\Services\SomethingService` (or subscribes to an event). It never does `use App\Modules\A\Models\Foo;`.
3. **Side effects via events.** When something happens that other modules care about, publish a domain event (`AccountVerified`, `BaseLiked`, `ReportFiled`). Do not reach across and call the other module directly.
4. **Thin HTTP layer.** Controllers/Livewire components authorize (Policy) and delegate to a Service or Action. No business logic in `Http/`. **Validation is defined in a `FormRequest` only** — the action type-hints it and reads `$request->validated()`; never `$request->validate()` inline. (Livewire: `rules()`/`#[Validate]` or a form object, never validation in the action body.)
5. **Actions are single-purpose.** One public method (`handle`/`__invoke`), named after the intent (`CreateBaseLayout`, `TransferAccountOwnership`). Reusable from HTTP, jobs, and tests.

## Steps to add a module

1. Confirm it's in the module list in `specs/02`. If not, that's a spec change — flag it first.
2. Create the folder structure above (only the parts you need).
3. Add migrations to the module's `migrations/`, matching the schema in [`specs/03-database.md`](../../../specs/03-database.md) — same table/column/index names.
4. Register the module's routes, events, and listeners (service provider or the app's module bootstrapping — follow how existing modules do it).
5. Write the Service/Action, then the thin Http layer, then Pest tests (happy path + authorization-denied).
6. If the module reacts to other modules, add Listeners; if others react to it, publish Events — document the event payload.

## Do not

- Do not create a module not in the spec without flagging it.
- Do not add a Redis-backed anything (see `AGENTS.md`).
- Do not duplicate a model that another module owns — read it via that module's Service.
- Do not leave placeholder files, empty classes, or TODO stubs. Build only what the task needs.

Verify with the architecture test (deptrac/pest) if configured; it should fail on illegal cross-module references.
