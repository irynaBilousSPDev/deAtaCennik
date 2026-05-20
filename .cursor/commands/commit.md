Create a git commit for this theme. The user invoked `/commit` — committing is allowed.

## Inspect first (run in parallel)

- `git status`
- `git diff` (staged and unstaged)
- `git log -5 --oneline` (match existing message style)

## Before staging

- Do **not** stage secrets (`.env`, credentials, keys).
- Do **not** commit broken Polish text (mojibake like `studiĂłw`, `ZAPISZ SIÄ`). Fix UTF-8 in PHP files first.
- Include **untracked** files that belong to the change (e.g. `configure/offer-pricing.php`, `.cursor/`, `template-parts/single-offer/pg-mba/`, `bachelor-master/`).

## Commit rules

- Never change `git config`.
- Never use `--no-verify` unless the user asked.
- Never `git commit --amend` unless the user asked and the last commit was yours and not pushed.
- Write a **1–2 sentence** message focused on **why**, not a file list.
- Use a proper multi-line message (HEREDOC on bash; on PowerShell use `git commit -m "title" -m "body"` or equivalent).

## Steps

1. `git add` only relevant paths (not unrelated junk).
2. `git commit` with the message.
3. `git status` after commit to confirm success.
4. If a pre-commit hook fails: fix the issue and make a **new** commit (do not amend).

## After commit

- **Do not push** unless the user also asked to push.
- Briefly summarize what was committed and mention anything left unstaged.
