---
description: Debug and fix a bug, then write a debug log note
argument-hint: <error message or description>
model: sonnet
---
Bug: $ARGUMENTS

1. Check `notes/Debug-Logs/` for a similar past bug and the relevant `notes/Modules/` note first.
2. Find the root cause before changing anything. Make the smallest correct fix. No code comments.
3. Run `sh tools/php32.sh -l <file>` / `node --check` on changed files.
4. Create `notes/Debug-Logs/YYYY-MM-DD <short title>.md` from `notes/Templates/Debug Log.md` (symptoms, repro, root cause, fix, files changed, lesson). Set `module: "[[Module Name]]"` and `status: fixed` (or `open`).
5. If it revealed a gotcha, add one bullet to the module note's "Business rules / gotchas".
