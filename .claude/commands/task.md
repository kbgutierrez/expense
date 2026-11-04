---
description: Implement a task note from notes/Tasks
argument-hint: <task note name>
model: sonnet
---
Implement the task in `notes/Tasks/$ARGUMENTS.md`.

1. Read the task note, then the module notes it mentions (`notes/Modules/`) and `notes/Decisions/`. Only then open the code files listed under "Mga apektado".
2. If requirements are ambiguous, ask before coding.
3. Work in this folder (`expense-test`) only. Follow existing patterns: plain model first, SP only where the decision note allows. No code comments.
4. Run `php -l` (`sh tools/php32.sh -l <file>`) / `node --check` on changed files.
5. Update the task note: tick finished checkboxes, set `status: done` (or `in-progress`), add a short "## Ginawa" section listing changed files.
6. Run `sh tools/php32.sh tools/gen_notes.php`; if behavior changed, also update the hand-written part of the module note.
