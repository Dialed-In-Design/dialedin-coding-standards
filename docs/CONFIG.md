# Project Configuration — `dialedin.toml`

Each WordPress project using Dialed In coding standards can ship a `dialedin.toml` file at the project root to enable or disable individual checks. This file is **optional**. When absent or partially populated, every check is enabled by default.

## When to use it

Most projects won't need this file. Only create one when a specific check **cannot run** on a project — typically due to upstream tool incompatibility, not because a check is inconvenient. Disabling a check creates a permanent hole in enforcement; document it.

Common legitimate reasons to disable a check:

| Scenario | Disable | Why |
|---|---|---|
| Sage 9 theme with old Illuminate Container | `psalm` | Psalm crashes on PHP 8 autoload of incompatible vendor code |
| WordPress plugin-only project (no theme code) | `phpstan` or `psalm` (one of) | Limited custom code surface; one analyzer is enough |
| Pure documentation/translation repo | `phpcs`, `phpstan`, `psalm` | No PHP code to analyze |
| Greenfield project with no composer.lock | `composer_audit` | Audit auto-skips when lockfile missing, but explicit is clearer |

Reasons that are **not** legitimate (don't disable for these):

- "We have too many violations" → use baselines (`phpstan analyse --generate-baseline`)
- "It's slow" → optimize the paths or scope (`phpstan.neon` `excludePaths:`)
- "It's flaky" → file an issue against the standards bundle

## File location

`dialedin.toml` lives at the **project root**, the same level as `composer.json` and `package.json`.

## Schema

```toml
[checks]
phpcs          = true
eslint         = true
stylelint      = true
gitleaks       = true
phpstan        = true
psalm          = true
composer_audit = true
npm_audit      = true

[disabled_reasons]
# psalm = "Reason here, with date and re-evaluation trigger"
```

### `[checks]` table — required if present

Boolean per check. Every key defaults to `true` if omitted. The file as a whole is optional — projects without a `dialedin.toml` get all checks enabled.

Recognized keys:

| Key | Hook | What it gates |
|---|---|---|
| `phpcs` | pre-commit | PHP coding standards + WordPress security sniffs |
| `eslint` | pre-commit | JS linting (only runs if JS files staged) |
| `stylelint` | pre-commit | CSS linting (only runs if CSS files staged) |
| `gitleaks` | pre-commit | Secret scanning across all staged content |
| `phpstan` | pre-push | PHP static analysis on configured paths |
| `psalm` | pre-push | PHP taint analysis + type inference |
| `composer_audit` | pre-push | CVE scan of PHP dependencies (auto-skipped if no composer.lock) |
| `npm_audit` | pre-push | CVE scan of JS dependencies, high-severity only (auto-skipped if no package-lock.json) |

Unrecognized keys are ignored — won't error, won't disable anything.

### `[disabled_reasons]` table — optional but strongly recommended

Free-form strings documenting why each disabled check is off. Keys must match a `[checks]` key. Values are arbitrary text.

This information is **not used by tooling**. It exists for humans reading the file in 6 months wondering why there's a hole in enforcement.

Suggested format:

```toml
[disabled_reasons]
psalm = "Sage 9 + Illuminate Container PHP 8 incompatibility. Re-evaluate at Sage 10 migration. Disabled 2026-05-14."
```

Include:
- The technical reason (one sentence)
- The condition under which to re-evaluate (when does this become fixable?)
- The date you disabled it (helps spot stale exemptions)

## Examples

### Minimal — only disable Psalm

```toml
[checks]
psalm = false

[disabled_reasons]
psalm = "Sage 9 incompatibility, see issue #123"
```

Everything else defaults to enabled.

### Plugin-only project — no theme code

```toml
[checks]
phpstan = true
psalm   = false   # Plugin doesn't need taint analysis layer

[disabled_reasons]
psalm = "Plugin uses only WordPress APIs; PHPStan + PHPCS sufficient. Re-evaluate if plugin grows."
```

### Greenfield — start with full enforcement

No `dialedin.toml` at all. Defaults to all checks active. Create the file only when you actually need to disable something.

## How disable works under the hood

The standards bundle's `lefthook.yml` reads `dialedin.toml` via a `grep` pattern in each command's `skip:` clause:

```yaml
psalm:
  skip:
    - run: '[ -f dialedin.toml ] && grep -E "^psalm[[:space:]]*=[[:space:]]*false" dialedin.toml >/dev/null'
  run: vendor/bin/psalm ...
```

When the grep matches (the line `psalm = false` exists), lefthook skips the command. When it doesn't (the file is missing, the key is absent, or the value is `true`), the command runs.

### Limitations to be aware of

Because the reader is a `grep` against the raw TOML text, it has limits:

1. **Only `=false` is recognized as "disabled"** — not `=False`, `="false"`, or any other variation. Stick to lowercase boolean values.
2. **Comments don't toggle** — `# psalm = false` is a comment, the check stays enabled.
3. **Nested tables (`[checks.something]`) won't work** — keep the schema flat.
4. **No type validation** — `phpcs = "yes"` won't enable phpcs, won't error, will just default-enable. The TOML file is for human readability, not strict validation.

For most use cases, these limitations don't matter. If a project needs schema validation, that's a v0.6.0 feature, not v0.5.0.

## Migration from earlier versions

Projects on v0.4.0 or earlier work unchanged — without a `dialedin.toml`, every check is enabled, matching the v0.4.0 behavior.

To opt into the new mechanism on an existing project:

1. Re-run `bin/init` (idempotent — won't overwrite existing configs, will create `dialedin.toml` if missing)
2. Edit `dialedin.toml` to flip checks to `false` as needed
3. Update `lefthook.yml` to reference `ref: v0.5.0` if it points at an older tag
4. `composer update dialed-in-design/coding-standards`
5. `npx lefthook install`

## What if I need more than enable/disable?

If you need:

- Custom paths per project → edit `phpstan.neon` / `psalm.xml.dist` / `phpcs.xml.dist` directly
- Custom severity levels → same, edit the tool-specific config
- Per-environment behavior (CI vs local) → use Lefthook's environment-aware features (`only:` / `skip:` with shell conditions)
- Different tool versions → that's a `composer.json` constraint, not a coding-standards override

`dialedin.toml` is intentionally narrow: it answers "is this check on or off for this project?" Anything more granular goes in the tool's own config.

## FAQ

**Q: Should `dialedin.toml` be committed to git?**
Yes. It's project-level configuration — every developer on the project should see the same set of enabled checks. Per-developer overrides go in `lefthook-local.yml` (gitignored).

**Q: What if I add a new check in v0.6.0 — do existing projects break?**
No. New checks default to enabled. Existing `dialedin.toml` files won't mention them. That's fine — the absence of a key means "enabled".

**Q: Can I disable a check just for one developer, not the whole project?**
Yes — use `lefthook-local.yml` instead. That file is gitignored and applies only to the cloning developer. Useful for "I can't install gitleaks on my machine" but not appropriate for project-level decisions.

**Q: My `dialedin.toml` has `psalm = false` but Psalm still runs. Why?**
Check three things, in order:
1. Is the file at the project root (same level as `composer.json`)?
2. Did you run `npx lefthook install` after editing? (Not strictly required, but rules out stale hooks.)
3. Is the line **exactly** `psalm = false` (no quotes around `false`, no leading whitespace)?

If all three are correct, run `grep -n "psalm" dialedin.toml` and paste the output — likely a typo.
