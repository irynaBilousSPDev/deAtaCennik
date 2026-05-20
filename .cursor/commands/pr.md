Create a pull request for this theme using the GitHub CLI.

## Before the PR

1. Run `git status`, `git diff`, and `git log -5` to see uncommitted work and commit message style.
2. If there are uncommitted changes the user wants in the PR, stage and commit them first (only when appropriate — user may have asked explicitly for a PR).
3. Ensure new files are not left untracked (e.g. `configure/offer-pricing.php`, `template-parts/single-offer/pg-mba/`, `template-parts/single-offer/bachelor-master/`).

## Push target

- Default: push to **`origin`** → https://github.com/irynaBilousSPDev/deAtaCennik.git
- Use remote **`ata2026`** only if the user says so.

```bash
git push -u origin HEAD
```

## Create PR

Always use `gh pr create` with a **descriptive title** and a body that includes:

### Summary

- 1–3 bullets: what changed and why.

### Test plan

Checklist tailored to the diff, for example:

- [ ] Bachelor/master single offer: calculator loads (`logical_sync_key`), `#tuition_fees`, “SPRAWDZ CENNIK”
- [ ] PG/MBA single offer: ACF payments + price table tabs + bank info (PL)
- [ ] Prices page template still works
- [ ] `npm run build` if `assets/src/` changed
- [ ] Permalinks flushed if CPT/taxonomies changed

Return the PR URL when done.
