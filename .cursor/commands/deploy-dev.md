Deploy the Akademiata theme to **dev** — branch **`dev`**, local commit optional, SFTP, then **push `dev` to GitHub**.

The user invoked `/deploy-dev` — **commit**, **deploy**, and **push** are allowed. **Never commit** `deploy.local.env`.

## Branches

| Branch | Use |
|--------|-----|
| **`dev`** | Day-to-day work + `/deploy-dev` (SFTP to dev.akademiata.pl + push) |
| **`main`** | Production; updated via **`/deploy-prod`** or **`/pr`** |

## Flow (commit if needed → SFTP → push)

```mermaid
flowchart TD
  A[git checkout dev] --> B{Uncommitted changes?}
  B -->|yes| C[commit on dev]
  B -->|no| D[npm run deploy:dev]
  C --> D
  D --> E[git push origin dev]
```

### 1. Branch and inspect

```bash
git checkout dev
```

Parallel: `git status`, `git diff`, `git log -3 --oneline`

### 2. Commit — only if dirty

Skip when clean. Do not stage `deploy.local.env`. Commit messages: **English only**.

### 3. Deploy (SFTP)

```bash
npm run deploy:dev
```

Uploads to `wp-content/themes/akademiata` on dev. `SKIP_BUILD=true` / `DRY_RUN=true` in `deploy.local.env` when needed.

### 4. Push `dev` to GitHub

```bash
git push origin dev
```

Keeps `origin/dev` in sync after every dev deploy.

## Skip git entirely

**Deploy only** / **without commit**: run only `npm run deploy:dev` on branch `dev` (no push unless user asks).

## Push only (no SFTP)

Use **`/push-dev`** when you only need GitHub backup without uploading to dev.

## Production

Use **`/deploy-prod`**: merge `dev` → `main`, push **`main` + `dev`**, SFTP to production — not this command.

## Do not

- Push to `main` from `/deploy-dev`.
- Commit `deploy.local.env`.
- Deploy production unless explicitly asked.
