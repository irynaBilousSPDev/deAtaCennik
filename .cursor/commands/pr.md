Commit (if needed), push to GitHub, and optionally open a pull request.

The user invoked `/pr` — committing and pushing are allowed.

**Team context:** Solo developer — **`main` only is the normal workflow**. Do not require feature branches unless the user asks for a PR with review or the repo blocks direct pushes to `main`.

## 1. Inspect (run in parallel)

- `git status`
- `git diff` (staged and unstaged)
- `git log -5 --oneline`

## 2. Commit (if there are uncommitted changes)

Skip if the working tree is already clean.

**Before staging**

- Do not stage secrets (`.env`, credentials, keys).
- Fix broken Polish text in PHP (mojibake like `studiĂłw`, `ZAPISZ SIÄ`) before commit.
- Include untracked files that belong to the change.

**Commit rules**

- Never change `git config` or use `--no-verify` unless the user asked.
- Never `git commit --amend` unless the user asked and the last commit is yours and not pushed.
- **Commit messages: English only** (imperative, concise). UI copy in PHP stays Polish — separate from git text.
- Message: 1–2 sentences, focus on **why** (match recent `git log` style).

**Steps:** `git add` (relevant paths only) → `git commit` → `git status`.

If a hook fails: fix and make a **new** commit (do not amend).

## 3. Push to `main` (default)

- Branch: stay on **`main`** unless the user asked for a feature branch.
- Remote: **`origin`** → https://github.com/irynaBilousSPDev/deAtaCennik.git
- Use **`ata2026`** only if the user says so.

```bash
git push -u origin main
```

(If already on another branch they chose, `git push -u origin HEAD`.)

## 4. Pull request (optional)

Try **`gh pr create`** only if the user asked for a PR **or** you are on a branch other than `main`.

- On **`main`** after push: a PR is usually **not needed**. Return:
  - confirmation that `origin/main` is updated
  - **compare link** for the last push if useful: `https://github.com/irynaBilousSPDev/deAtaCennik/compare/<before>...<after>`
- If they want a PR from `main` anyway: create `feature/...` first (English slug), push that branch, then `gh pr create --base main --head feature/...` with English title and body (Summary + Test plan).

**Branch names (when used):** English only — `feature/hero-slider`, `fix/tuition-tabs`, `chore/cursor-rules`.

### Test plan (PR body or push summary)

- [ ] Bachelor/master offer: calculator, `#tuition_fees`
- [ ] PG/MBA offer: ACF payments + price table
- [ ] Prices page calculator
- [ ] `npm run build` if `assets/src/` changed
- [ ] Permalinks flushed if CPT/taxonomies changed

## Commit or push only?

- **Commit only:** step 2, then stop.
- **Push only:** step 3, then stop.
