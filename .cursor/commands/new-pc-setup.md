New PC setup (Windows) for Akademiata theme — make **/deploy-dev**, **/deploy-prod**, **/push-dev**, **/pr** work.

The user invoked `/new-pc-setup` — provide commands + checklist. **Never commit** `deploy.local.env`.

## 0) Install tools (one-time per PC)

```powershell
winget install --id Git.Git
winget install --id OpenJS.NodeJS.LTS
```

Close PowerShell, open a new one, verify:

```powershell
git --version
node -v
npm -v
```

## 1) Clone the repo

```powershell
cd "$env:USERPROFILE\Documents"
git clone https://github.com/irynaBilousSPDev/deAtaCennik.git
cd deAtaCennik
```

## 2) Install dependencies (one-time per clone)

```powershell
npm ci
```

## 3) Create `deploy.local.env` (one-time per PC, NOT in git)

Copy `deploy.local.env.example` → `deploy.local.env`, then fill in real credentials.

- DEV uses: `SFTP_*`
- PROD uses: `SFTP_PROD_*`

Minimum required keys:

```env
SFTP_HOST=dev.akademiata.pl
SFTP_PORT=22
SFTP_USER=YOUR_DEV_USER
SFTP_REMOTE_PATH=wp-content/themes/akademiata
SFTP_USE_PASSWORD=true
SFTP_PASSWORD=YOUR_DEV_PASSWORD

SFTP_PROD_HOST=akademiata.pl
SFTP_PROD_PORT=22
SFTP_PROD_USER=YOUR_PROD_USER
SFTP_PROD_REMOTE_PATH=wp-content/themes/akademiata
SFTP_PROD_USE_PASSWORD=true
SFTP_PROD_PASSWORD=YOUR_PROD_PASSWORD

SKIP_BUILD=true
# DRY_RUN=true
```

Quick check:

```powershell
Test-Path .\deploy.local.env
```

## 4) Which branch is which?

- **dev**: day-to-day work + `/deploy-dev`
- **main**: production + `/deploy-prod`
- **Sync from GitHub (other PC worked yesterday):** `/sync-git` or `npm run sync:git` — no SFTP

**Rule:** GitHub = source of truth. End session: `/deploy-dev` (commit + push + dev server). Start on other PC: `/sync-git`.

## 5) Sanity check (if something fails)

```powershell
git remote -v
git branch --show-current
npm -v
```

## Do not

- Commit `deploy.local.env` (it contains secrets).
- Run `/deploy-prod` without correct `SFTP_PROD_*` values.

