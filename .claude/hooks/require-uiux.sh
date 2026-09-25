#!/usr/bin/env bash
# PreToolUse gate: when Claude is about to write a UI file, require that the
# ui-ux-pro-max skill (and antislop) has been applied first. It surfaces a
# permission prompt ("ask") on every UI-file write, so a UI edit can't slip
# through unreviewed. Non-UI files pass untouched. See CLAUDE.md "antislop".
set -euo pipefail

input="$(cat)"

# Edit/Write/MultiEdit all carry the target as tool_input.file_path.
fp="$(printf '%s' "$input" | jq -r '.tool_input.file_path // ""')"

case "$fp" in
  *.blade.php|*.css|*.scss|*.sass|*.less|*.vue|*.svelte|*.jsx|*.tsx)
    cat <<'JSON'
{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"ask","permissionDecisionReason":"UI file edit. Per CLAUDE.md, load and apply the ui-ux-pro-max skill (plus the relevant antislop skills) before writing UI. Approve only once you've run them for this work."}}
JSON
    ;;
  *)
    # Not a UI file: stay silent so normal permission handling applies.
    :
    ;;
esac
