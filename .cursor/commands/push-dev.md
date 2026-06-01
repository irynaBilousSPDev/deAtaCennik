Push branch **`dev`** to GitHub only (no SFTP).

Use when you want to back up `dev` without deploying. For normal dev work, **`/deploy-dev`** already SFTPs and pushes.

```bash
git checkout dev
git push origin dev
```

Do not push `deploy.local.env`. If there are unpushed commits, push them; if up to date, say so.
