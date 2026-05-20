Audit the Akademiata theme and **update `.cursor/rules/*.mdc`** so they match the current codebase.

## Rule files (update what is stale)

| File | Verify against |
|------|----------------|
| `akademiata-theme.mdc` | `functions.php`, `style.css`, remotes, `configure/*` load order, integrations |
| `akademiata-cpts.mdc` | `configure/cpt-taxonomy.php` — all CPTs, taxonomies, rewrites |
| `akademiata-templates.mdc` | Root `single-*.php`, `page-template-*.php`, `template-parts/`, `partials/` |
| `akademiata-pricing.mdc` | Calculator + PG/MBA paths, `offer-pricing.php`, `js-css.php`, no removed CPTs |
| `akademiata-assets.mdc` | `package.json`, `webpack.config.js`, `gulpfile.js`, `assets/src/` |
| `akademiata-maintain-rules.mdc` | This workflow — only edit if the process itself changed |

## Process

1. Scan the repo (do not rely only on chat memory).
2. Read each existing `.mdc` file.
3. Fix **outdated** entries: wrong paths, removed CPTs, old template names, wrong globs.
4. Add **missing** facts that agents need often (new CPT, new template folder, new npm script).
5. Remove bullets that describe code that no longer exists.
6. Keep each rule file **short and factual** (prefer tables/lists over prose).
7. Preserve valid YAML frontmatter (`description`, `globs`, `alwaysApply`).

## Do not

- Invent features not present in the repo.
- Bloat rules with chat-only history or “do not restore” lists unless still relevant architecture.
- Change `.cursor/commands/` unless the user asked.
- Commit unless the user invoked `/pr` or asked to commit.

## Output

Reply with:

- **What was outdated** (brief list)
- **What you changed** (per file)
- **Anything uncertain** that needs the user to confirm

If everything already matches, say so and note any optional improvements.
