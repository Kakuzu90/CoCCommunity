---
name: coc-integration
description: Work with the Clash of Clans API — token ownership verification, account/clan snapshot sync, and the anti-corruption layer. Use whenever code touches the external CoC API, coc_accounts, or coc_account_snapshots. Enforces the never-call-the-API-in-a-request-path rule.
---

# CoC integration

The official CoC API is wrapped behind a `ClashClient` interface (the `CocIntegration` module). Nothing outside that module talks HTTP to Supercell. The rest of the app reads persisted snapshots.

Reference: [`specs/04-coc-integration-and-claiming.md`](../../../specs/04-coc-integration-and-claiming.md).

## Hard rules

1. **Never call the CoC API in a web request path.** All calls happen inside queued jobs (`VerifyCocToken`, `SyncCocAccount`, `SyncClan`). A controller/Livewire component dispatches a job and reads from the DB.
2. **Only `CocIntegration` depends on `ClashClient`.** Feature code reads `coc_accounts` + `coc_account_snapshots`, never the client.
3. **Ownership = `verifytoken`.** The only accepted proof of ownership is a successful `POST /players/{tag}/verifytoken` with the user's in-game API token. There is no other verification path.
4. **`coc_accounts.tag` is globally unique.** Enforced at the DB. Verifying a tag already verified by someone else triggers the ownership-transfer flow (below), never a silent second attach.
5. **Store the token encrypted.** The in-game API token is a secret — encrypted at rest, never logged, never returned in responses.

## Reading player data

- Serve from the latest `coc_account_snapshots` row. Show `last_synced_at` so users know it isn't live.
- Snapshot detail (heroes/troops/spells/etc.) lives in the JSONB `data` column — don't explode it into child tables.
- Never block a page render on a fresh API call. If data is missing, show "syncing" and let the job populate it.

## Sync

- Scheduled refresh of verified accounts is staggered (every 6–12 h) to respect rate limits; clan data daily.
- Manual "refresh now" is throttled per user.
- Jobs use exponential backoff on 5xx/429, a circuit breaker during outages, and dead-letter after max attempts.
- On sustained failure or a tag that stops resolving: set the account to `needs_reverify`, keep existing data, stop syncing. Never delete snapshot history on a transient error.

## Ownership transfer (conflict path)

When a second user proves ownership of an already-verified tag via `verifytoken`:
1. Current token control wins — the new holder becomes the verified owner.
2. Write an `audit_logs` row: old owner, new owner, method, actor.
3. Notify the previous owner.
4. Apply the base-reassignment/hiding policy for the old owner's content.

Disputes where token proof isn't possible go to admin review (`coc_account_claims`), not automatic transfer.

## Testing

- Fake `ClashClient` in tests — no live network. Cover: successful verify, failed verify, tag conflict/transfer, API 429/5xx retry, and the `needs_reverify` transition.

## Do not

- Do not add a second verification mechanism (email, screenshot-only) as a substitute for `verifytoken`.
- Do not cache the API response as the source of truth — snapshots are the source of truth; API-response caching is only a brief dedupe (see [`specs/10-infrastructure.md`](../../../specs/10-infrastructure.md)).
- Do not hardcode API keys; they're IP-bound encrypted secrets.
