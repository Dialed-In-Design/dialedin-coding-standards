# v0.5.1 — Patch release

Bug fixes only. No new features. Backwards-compatible.

## What's fixed

### 1. `phpstan.neon` baseline location (PHPStan 2.x compatibility)

**The bug:** v0.5.0 (and earlier) generated `phpstan.neon` with the baseline reference under `parameters:`:

```yaml
parameters:
    # baseline: phpstan-baseline.neon
```

PHPStan 2.x rejects this with:

```
Invalid configuration:
Unexpected item 'parameters › baseline'.
```

The `baseline:` key moved to top-level `includes:` in PHPStan 2.x. The bundle's template was still using the v1.x location.

**The fix:** New `phpstan.neon` template emits the baseline under `includes:`:

```yaml
includes:
    - vendor/dialed-in-design/coding-standards/phpstan.neon.dist
    # Uncomment after: vendor/bin/phpstan analyse --generate-baseline
    # - phpstan-baseline.neon
```

The auto-uncomment `sed` in `bin/init`'s baseline-generation step now targets the new location.

**Impact:** Every project on v0.4.x and v0.5.0 hit this bug after baseline generation. Two pilots (OGP Stories, Insightly) confirmed it. v0.5.1 makes the gate work as designed without manual intervention.

### 2. Self-execution protection in `bin/init`

**The bug:** `bin/init` had no guard against running inside the standards repository itself. A near-miss during v0.5.0 release nearly committed a self-referential `composer.json` and `package.json` to the standards repo (the init script merged the standards bundle into its own dependency list).

**The fix:** Early validation in `bin/init` checks `composer.json` for `"name": "dialed-in-design/coding-standards"`. If present, the script refuses to proceed:

```
✗ This appears to be the dialed-in-coding-standards repository itself.

  The init script is for downstream consumer projects, not for the bundle.
  Running it here would self-reference the package as its own dev dep and
  corrupt composer.json/package.json.

  If you intended to run this inside a downstream WordPress project, cd there
  first and re-run.
```

**Impact:** Prevents an entire class of accidents. The check is silent on downstream projects (different composer.json name → no warning, no slowdown).

## Files changed

- `bin/init` — two distinct fixes:
  - phpstan.neon template now writes baseline under `includes:`
  - Self-execution protection added during environment validation
- Version constants bumped to 0.5.1

## Upgrade path

Backwards-compatible patch. Existing projects auto-upgrade on next `composer update`:

```bash
composer update dialed-in-design/coding-standards
sed -i -E 's,ref: v0\.(3|4|5)\.[0-9]+,ref: v0.5.1,' lefthook.yml
npx lefthook install
```

For existing projects with the broken `# baseline: phpstan-baseline.neon` line under `parameters:`, you'll need a one-time manual fix:

```bash
# Remove the broken line from parameters:
sed -i '/# baseline: phpstan-baseline.neon$/d' phpstan.neon

# Add the correct line to includes:
sed -i 's|^    - vendor/dialed-in-design/coding-standards/phpstan.neon.dist|    - vendor/dialed-in-design/coding-standards/phpstan.neon.dist\n    - phpstan-baseline.neon|' phpstan.neon
```

Or just regenerate `phpstan.neon` by deleting it and re-running `bin/init`.

## Testing performed

- ✓ Bash syntax valid
- ✓ `--version` reports 0.5.1
- ✓ Self-execution protection fires when composer.json has `name: dialed-in-design/coding-standards`
- ✓ Self-execution protection silent on downstream projects (different package name or no composer.json)
- ✓ Generated `phpstan.neon` has `# - phpstan-baseline.neon` under `includes:`, NOT under `parameters:`
- ✓ Auto-uncomment sed correctly transforms `# - phpstan-baseline.neon` → `- phpstan-baseline.neon`
- ✓ Resulting YAML is valid against PHPStan 2.x schema

## What this DOES NOT fix (still open for future patches)

The two-pilot validation surfaced more issues. These are NOT addressed in v0.5.1 to keep the patch focused:

- Expanded known-vendor plugin list (false positives across pilots: add-to-any, autoptimize, cloudflare, demandwell, perfmatters, rank-math-seo slug, and several others)
- MU-plugins blanket-as-custom heuristic (pantheon-mu-plugin and similar should be filtered)
- Active vs legacy heuristic refinement (OGP's `ogp-stories` was misclassified as active)
- Theme-local Composer autoloader detection (Sage-style projects)
- minimatch ReDoS chain in standards bundle's own npm dependencies

These remain on the v0.5.2 / v0.6.0 patch list pending more pilot data.
