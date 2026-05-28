---
name: deploy-workflows
description: Deploy the Akademiata WordPress theme to dev or production using the repo's npm scripts and SFTP env file. Use when the user says deploy-dev, deploy-prod, SFTP deploy, upload theme, or wants a new-PC setup for deploy commands.
disable-model-invocation: true
---

# Deploy workflows (Akademiata theme)

## Key files and rules

- Use `deploy.local.env` for credentials (required by `scripts/deploy-dev.js`).
- Never commit `deploy.local.env` (secrets). It is in `.gitignore`.
- `deploy.local.env.example` is a template only; the deploy script does **not** read it.
- Default deploy commands:
  - Dev: `npm run deploy:dev`
  - Prod: `npm run deploy:prod`

## `/deploy-dev` (fast SFTP, no push by default)

1. Ensure branch:

```bash
git checkout dev
```

2. Inspect:
- `git status`
- `git diff`
- `git log -3 --oneline`

3. Commit only if needed:
- English commit message
- Do not stage `deploy.local.env`

4. Deploy:

```bash
npm run deploy:dev
```

Notes:
- If you want faster deploys while editing PHP only, set `SKIP_BUILD=true` in `deploy.local.env`.
- For a safe preview, set `DRY_RUN=true` in `deploy.local.env`.
- Do not `git push` unless the user explicitly says “push dev”.

## `/deploy-prod` (merge dev → main, push main, then SFTP)

1. Finish work on `dev` (commit if dirty).
2. Merge into `main`:

```bash
git checkout main
git pull origin main
git merge dev -m "Merge branch 'dev' into main for production deploy."
```

3. Push `main`:

```bash
git push origin main
```

4. Deploy:

```bash
npm run deploy:prod
```

5. Return:

```bash
git checkout dev
```

## New PC setup (Windows)

1. Install:

```powershell
winget install --id Git.Git
winget install --id OpenJS.NodeJS.LTS
```

2. Verify in a new PowerShell:

```powershell
git --version
node -v
npm -v
```

3. Clone + deps:

```powershell
git clone https://github.com/irynaBilousSPDev/deAtaCennik.git
cd deAtaCennik
npm ci
```

4. Create `deploy.local.env` from `deploy.local.env.example` and fill:
- `SFTP_*` for dev
- `SFTP_PROD_*` for prod

## Common failures and fixes

- **`npm` not recognized** on Windows:
  - Install `OpenJS.NodeJS.LTS`, reopen PowerShell, re-check `npm -v`.
- **Missing `deploy.local.env`**:
  - Create `deploy.local.env` (do not edit the example file with secrets).
- **SFTP "No such file" / wrong remote path**:
  - Fix `SFTP_REMOTE_PATH` / `SFTP_PROD_REMOTE_PATH` to the real server path for `wp-content/themes/akademiata`.

