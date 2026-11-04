---
description: Deep debugging with Opus for bugs /bug couldn't solve (more expensive)
argument-hint: <error message or description>
model: opus
---
Hard bug (a previous attempt failed): $ARGUMENTS

1. Read related `notes/Debug-Logs/` (including any failed attempt) and the module note.
2. List plausible root causes, then verify each against the data flow (route → controller → model/SP → view → JS) and any logs the user provides. Don't guess.
3. Fix the confirmed cause with the smallest change; lint the changed files.
4. Create or update the debug log using `notes/Templates/Debug Log.md`, including what the earlier attempt missed.
