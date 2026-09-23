---
name: secure-feature
description: Checklist for adding any user-facing action (create/update/delete, upload, apply, report, order). Use before finishing any endpoint or Livewire action that reads or mutates user data. Enforces policy-first authorization, validation, rate limiting, and audit logging from specs/06.
---

# Secure feature

Run this checklist for every user-facing action. It closes the threats in [`specs/06-auth-and-security.md`](../../../specs/06-auth-and-security.md). Skipping a step is how IDOR, mass-assignment, and scam bugs ship.

## Authorization (policy-first)

- The action calls a Model Policy: `$this->authorize('update', $base)` or `->can(...)`. No inline ownership checks in controllers/Blade.
- The policy checks **ownership by `user_id`** *and* role where relevant. A User touches only their own resources; Moderator/Admin get elevated policies.
- Never trust an ID from the request to imply access. Load the resource, then authorize it.
- Write a Pest test that a non-owner and the wrong role are **denied**.

## Input & output

- **Validation is defined in a `FormRequest` only** — the action reads `$request->validated()`. No `$request->validate()` or `Validator::make()` inside the controller/action. (Livewire: `rules()`/`#[Validate]` or a form object.)
- Pass `$request->validated()` into the action/model. Never `$request->all()`.
- Models use explicit `$fillable`; no client-controlled `role`, `status`, `user_id`, `verified_at`, or counter fields.
- Output is escaped (Blade auto-escapes; sanitize any rich text/markdown). No `{!! !!}` on user input.

## Abuse resistance

- Rate-limit the endpoint if it mutates state, sends messages, or is enumerable (login, apply, report, order, verify).
- On auth flows, keep responses uniform so they don't reveal whether an email exists.
- Dedupe/throttle mass actions (report brigading, repeat applications) — enforce the unique constraints from the `database-schema` skill.

## Uploads (if the action takes a file)

Defer to the media flow in [`specs/05-media-storage.md`](../../../specs/05-media-storage.md):
- Signed, short-lived upload URL to R2; validation job flips `pending → ready/rejected`.
- Check MIME **and** magic-byte signature, enforce size/count, re-encode images, scan. Never trust the extension.
- Serve from the cookieless media domain; private/pending media via signed URLs only.

## Audit

- Sensitive mutations (ownership transfer, role change, suspension/ban, moderation decision, dispute resolution) write an `audit_logs` row: actor, action, subject, before/after, ip.

## Money

- There is no fund custody. If the action is marketplace-related, it must not hold, move, or promise to release money (see `AGENTS.md` and [`specs/09-marketplace.md`](../../../specs/09-marketplace.md)).

## Before you call it done

- Relevant Pest tests pass, including the authorization-denied test. Report real results.
- Consider running the built-in `security-review` skill on the diff if it touches auth, uploads, ownership, or marketplace.

## Do not

- Do not add an endpoint without a policy.
- Do not log secrets, tokens, or full request bodies.
- Do not leave validation "to add later" — an unvalidated mutation is not shippable.
