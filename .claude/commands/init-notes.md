---
description: One-time pass to fill the hand-written parts of the Obsidian notes (module purpose and gotchas)
model: sonnet
---
Run `sh tools/php32.sh tools/gen_notes.php` first. It fills every note between `%% gen:start %%` and `%% gen:end %%` (files, endpoints, models, SP, tables). Never edit inside those markers.

Then, for each note in `notes/Modules/` (frontmatter `module:` = folder in `application/modules/`) whose "Ano ang ginagawa" is empty or "Business rules / gotchas" is just `- `:
1. Read the controller and the models listed in the note's Endpoints table (Grep first; skip anything in the CLAUDE.md "Don't read" list).
2. Write one sentence under "Ano ang ginagawa" (who uses it, for what).
3. Write 3 to 8 bullets under "Business rules / gotchas": role/session checks, status transitions, approval/payment flags, K-Flow behavior, 32-bit or encryption concerns, mobile `lqm-*` usage, anything surprising.
4. Set `updated:` to today.

Rules: don't modify code; use `[[wikilinks]]`; keep notes concise; respect `notes/Decisions/`. End with a short list of what was filled and what you couldn't determine.
