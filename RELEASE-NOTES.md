# v0.5.0 — Per-project check enable/disable via `dialedin.toml`

## What's new

Each project can now declare which checks to enable or disable by committing a `dialedin.toml` file at the project root. The file is **optional** — without it, every check defaults to enabled, matching v0.4.x behavior exactly.

## The mechanism

Every command in the standards bundle's `lefthook.yml` now has a `skip:` clause that reads `dialedin.toml`. If the file contains `<check_name> = false`, lefthook skips that command. Otherwise the command runs as normal.

Eight checks are configurable:

| Hook | Checks |
|---|---|
| pre-commit | `phpcs`, `eslint`, `stylelint`, `gitleaks` |
| pre-push | `phpstan`, `psalm`, `composer_audit`, `npm_audit` |

## Why this exists

Pilot testing on OGP Stories (a Sage 9 / PHP 8 project) showed that Psalm crashes on the theme's old Illuminate Container code. Disabling Psalm per-project needed to be possible without per-developer config files (`lefthook-local.yml`) — every developer on a project should see the same enforcement.

`dialedin.toml` solves this: commit the override file once, every developer on every clone of that project gets identical behavior. New developers don't need to know about the workaround.

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
# Optional but recommended — document why each disabled check is off.
# Free-form text, no enforced format. Example:
# psalm = "Sage 9 + Illuminate Container PHP 8 incompatibility. Re-evaluate at Sage 10 migration."
```

See [`docs/CONFIG.md`](docs/CONFIG.md) for full schema documentation.

## Migration from v0.4.x

Zero-config — existing projects work unchanged. Without a `dialedin.toml`, every check is enabled.

To opt into the new mechanism on an existing project:

```bash
# Re-run init (idempotent — creates dialedin.toml if missing)
curl -fsSL https://raw.githubusercontent.com/Dialed-In-Design/dialedin-coding-standards/v0.5.0/bin/init | bash

# Edit dialedin.toml to disable checks as needed
# (e.g. set psalm = false for Sage 9 projects)

# Re-sync lefthook hooks
npx lefthook install

# Update the lefthook remote ref
sed -i -E 's,ref: v0\.4\.[0-9]+,ref: v0.5.0,' lefthook.yml
```

## Limitations

The skip mechanism uses a `grep` pattern, not a real TOML parser. As a consequence:

- Only `<key> = false` (lowercase, unquoted) disables a check
- Comments (`# psalm = false`) don't toggle
- The `[checks]` section header is decorative — the grep matches keys anywhere in the file
- No type validation — `psalm = "no"` is treated as enabled (defaults apply)

These limits are acceptable for a feature-flag schema and avoid adding `tomlq`/`yq` as a new system dependency. Stick to the documented format.

## Files changed

- `lefthook.yml` — added `skip:` clauses to all 8 commands (~50 lines added)
- `bin/init` — bumped to v0.5.0; new section writes default `dialedin.toml` if missing
- `docs/CONFIG.md` — new file, full schema documentation

## Testing performed

- ✓ YAML syntax valid
- ✓ Bash syntax valid; `--version` reports 0.5.0
- ✓ Grep pattern matches `<key> = false` across whitespace variations
- ✓ Grep pattern correctly rejects: comments, quoted strings, `= true`
- ✓ Grep pattern correctly distinguishes `psalm` from `psalm_extra` (prefix safety)
- ✓ Missing `dialedin.toml` → all checks default-enabled (correct zero-config behavior)
- ✓ Partial `dialedin.toml` (only some keys present) → missing keys default-enabled
- ✓ `bin/init` writes default `dialedin.toml` on first run; skips if already present
- ⚠ End-to-end with real lefthook on real project — needs your validation

## What this DOESN'T cover (deliberately)

Not built in v0.5.0, deliberately deferred:

- **Detection result caching** — separate concern, no consumer yet
- **Project metadata** (PHP/WP version targets) — premature without a consumer
- **Per-project rule overrides** for PHPCS/PHPStan/Psalm — those tools have their own configs; duplicating in `dialedin.toml` would create two sources of truth
- **Strict TOML validation** — grep-based reader is sufficient for boolean flags; can revisit if usage outgrows it

## Honest caveat

This release was built based on data from **one pilot project** (OGP Stories). The schema may need to evolve as the second and third pilots surface different needs. Future versions may add fields or restructure — the `[checks]` table is the stable contract; `[disabled_reasons]` and any future additions are conventions.

If you hit a case the schema doesn't cover, file an issue rather than working around it. Pattern data across projects is what justifies schema additions.
