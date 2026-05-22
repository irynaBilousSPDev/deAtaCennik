Push branch **`dev`** to GitHub only (no SFTP).

Use when the user wants to back up `dev` to `origin` without a full `/deploy-dev` push step.

```bash
git checkout dev
git push origin dev
```

Do not push `deploy.local.env`. If there are unpushed commits, push them; if up to date, say so.
