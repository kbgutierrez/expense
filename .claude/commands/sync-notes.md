---
description: Refresh notes against the current code and DB
argument-hint: "[module or note name — optional]"
model: haiku
---
1. Run `sh tools/php32.sh tools/gen_notes.php` (no tokens spent reading code). It refreshes everything inside `%% gen:start %%` / `%% gen:end %%`.
2. Scope: $ARGUMENTS (if empty, only modules changed in the last 2 weeks per `git log --since="2 weeks ago" --name-only`). Check the hand-written parts (Ano ang ginagawa, Business rules / gotchas, Architecture, Database Schema status codes) against the code; fix stale lines.
3. Don't modify code. End with a short list of notes you changed.
