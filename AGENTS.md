See [CLAUDE.md](CLAUDE.md) — same working agreement applies to every agent in this repo.

## UI work: ui-ux-pro-max is mandatory
Before creating or editing any UI surface — Blade views/components, CSS (`*.css`, `*.scss`),
or any `.vue`/`.svelte`/`.jsx`/`.tsx` — you MUST load and apply the `ui-ux-pro-max` skill first
(query its design-system/domain guidance for the surface at hand), alongside the antislop skills
below. Do not begin UI edits until you have done so for the current work.

(In Claude Code this is also enforced by a `PreToolUse` hook in `.claude/settings.json`; other
agents such as Codex have no equivalent gate, so this rule is the guard here — follow it manually.)

<!-- antislop:start -->
## antislop
For UI, copy, people, mobile layout, or code comments work, load the antislop skill for the task:
- Core filter, always on: `antislop`
- UI / visual: `antislop-ui`
- Copy & text: `antislop-copywriting`
- People: `antislop-human`
- Mobile / responsive: `antislop-layoutmobile`
- Code comments: `antislop-code`
Before starting, ask the user when antislop applies: during the work, or after it is done.
To update antislop later: `npx antislop-ai --update`, or run `npx antislop-ai` and pick Overwrite them.
<!-- antislop:end -->
