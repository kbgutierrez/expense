---
description: End-of-day — write the daily log and refresh module notes
model: haiku
---
Write today's daily log and update notes. Keep it brief.

1. Find what changed today: `git log --since=midnight --name-only --oneline` and `git status --short`, plus what was done in this session.
2. Create or append `notes/Daily/YYYY-MM-DD.md` using `notes/Templates/Daily.md`: Ginawa, Mga file na binago, Pending.
3. Run `sh tools/php32.sh tools/gen_notes.php` to refresh endpoints/SP/tables. Edit the hand-written part of a module note only if behavior changed.
4. Make sure task notes worked on today have the right `status:`.
5. Don't modify code.
