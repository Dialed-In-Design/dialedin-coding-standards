# Dialed In Coding Standards — Project Onboarding

Add Dialed In coding standards (PHPCS, PHPStan, Psalm, ESLint, Stylelint, Lefthook git hooks, Gitleaks secret scanning) to any WordPress project, regardless of host (Pantheon, Bitbucket, WP Engine, Kinsta, GitHub).

**Time required:** ~5 minutes.

---

## Quick start

From the project root:

```bash
curl -fsSL https://raw.githubusercontent.com/Dialed-In-Design/dialedin-coding-standards/v0.3.0/bin/init | bash
```

That's it. The script:

1. Validates your environment (PHP 8.2+, Node 20+, Composer, jq).
2. Adds 7 config files at the project root (merging into existing `composer.json` and `package.json` where present — never overwrites without backup).
3. Runs `composer install`, `npm install`, `npx lefthook install`.
4. Copies `psalm.xml.dist` from `vendor/` into the project root.
5. Verifies the install (`phpcs -i` shows `DialedIn`, hooks registered, etc.).

### Preview without writing

```bash
curl -fsSL https://raw.githubusercontent.com/Dialed-In-Design/dialedin-coding-standards/v0.3.0/bin/init | bash -s -- --dry-run
```

### Download, inspect, then run

If you want to read the script before executing it:

```bash
curl -fsSL -o /tmp/dialedin-init https://raw.githubusercontent.com/Dialed-In-Design/dialedin-coding-standards/v0.3.0/bin/init
less /tmp/dialedin-init
bash /tmp/dialedin-init
```

---

## Prerequisites

The init script will check these for you and fail fast if anything is missing. Listed here so you can pre-install:

| Requirement | Check | Install |
|---|---|---|
| PHP 8.2+ | `php -v` | Pantheon/Kinsta defaults are fine. Legacy 7.4/8.0 sites must upgrade first. |
| Node 20 LTS+ | `node -v` | https://nodejs.org |
| Composer 2.x | `composer --version` | https://getcomposer.org |
| jq | `jq --version` | `brew install jq` / `apt install jq` |
| Gitleaks (optional) | `gitleaks version` | `brew install gitleaks`. Pre-commit hook degrades gracefully if absent. |

Working tree should be clean — commit or stash first.

---

## Post-install steps

The script handles the bulk of setup, but two things need per-project tuning afterward.

### 1. Adjust analysis paths

Edit `phpstan.neon` and `psalm.xml.dist` so they scan **only your custom code**, not WordPress core or third-party plugins.

**`phpstan.neon`** — adjust the `paths:` block:
```neon
parameters:
    paths:
        - wp-content/themes/your-theme
        - wp-content/mu-plugins
        - wp-content/plugins/your-custom-plugin  # only YOUR plugins
```

**`psalm.xml.dist`** — adjust `<projectFiles>` similarly, and remove any `<ignoreFiles>` entries pointing at paths that don't exist in this project (Psalm errors out on missing paths).

### 2. Baseline existing violations (legacy projects only)

A WordPress project with years of history will report thousands of violations on day one. Block only **new** code from regressing — leave the backlog for incremental fixes.

```bash
# PHPStan baseline
vendor/bin/phpstan analyse --generate-baseline

# Psalm baseline
vendor/bin/psalm --set-baseline=psalm-baseline.xml

# Then uncomment 'baseline:' in phpstan.neon
```

PHPCS has no native baseline — instead, the pre-commit hook scans only staged files, so legacy code is untouched until someone edits it.

Commit the baseline files:
```bash
git add phpstan-baseline.neon psalm-baseline.xml phpstan.neon
git commit -m "chore: baseline existing static analysis violations"
```

### 3. Commit the lockfiles

```bash
git add composer.json composer.lock package.json package-lock.json \
        phpcs.xml.dist phpstan.neon psalm.xml.dist \
        .eslintrc.json stylelint.config.js lefthook.yml
git commit -m "chore: add Dialed In coding standards (v0.3.0)"
```

**Do not gitignore lockfiles.** They're build-critical — without `package-lock.json`, the CI workflow fails at `cache: npm`; without `composer.lock`, builds are non-reproducible.

---

## Testing the gates

Confirm each gate actually fires before you trust it.

### Pre-commit gate (PHPCS / ESLint / Stylelint / Gitleaks)

```bash
# Trigger PHPCS
cat > test-bad.php <<'EOF'
<?php
echo $_GET['name']; // unescaped output
EOF

git add test-bad.php
git commit -m "test: should be blocked"
# Expected: commit blocked, PHPCS reports WordPress.Security.EscapeOutput

git restore --staged test-bad.php && rm test-bad.php
```

### Secret scan (Gitleaks)

```bash
echo 'AWS_SECRET_ACCESS_KEY=AKIAIOSFODNN7EXAMPLE' > test-secret.env
git add test-secret.env
git commit -m "test: should be blocked"
# Expected: commit blocked, gitleaks reports the AWS key

git restore --staged test-secret.env && rm test-secret.env
```

### Pre-push gate (PHPStan / Psalm)

```bash
git checkout -b test/gate-validation
git commit --allow-empty -m "test: trigger pre-push"
git push origin test/gate-validation
# Expected: PHPStan + Psalm run before push. With baselines in place, passes.

git checkout - && git branch -D test/gate-validation
git push origin --delete test/gate-validation 2>/dev/null || true
```

---

## Daily workflow

After onboarding, the workflow is invisible:

1. `git clone <project>`
2. `composer install && npm install` ← hooks auto-install via `prepare` script
3. Write code
4. `git commit` → pre-commit runs PHPCS/ESLint/Stylelint/Gitleaks on staged files
5. `git push` → pre-push runs PHPStan + Psalm on full project
6. CI re-runs everything as a safety net

Manual commands:

```bash
composer lint            # PHPCS scan
composer lint:fix        # PHPCS auto-fix
composer analyze         # PHPStan
composer taint           # Psalm + taint analysis
composer check           # All three
npm run lint:js          # ESLint
npm run lint:js:fix      # ESLint auto-fix
npm run lint:css         # Stylelint
npm run lint:css:fix     # Stylelint auto-fix
```

---

## Updating to a new standards version

When a new tag is released (e.g. `v0.4.0`), re-run the init script with the new tag:

```bash
curl -fsSL https://raw.githubusercontent.com/Dialed-In-Design/dialedin-coding-standards/v0.4.0/bin/init | bash
```

The script is idempotent — it will update the pinned versions in `composer.json`, `package.json`, and `lefthook.yml`, then re-run installs. Test locally before committing the lockfile changes; a new ruleset version may surface previously-hidden violations.

---

## Bypassing the hooks (use sparingly)

```bash
git commit --no-verify        # Skip pre-commit
git push --no-verify          # Skip pre-push
```

CI runs the same gates, so bypassing locally only delays the failure. If a developer is bypassing routinely, the ruleset needs tuning — not the hooks disabled.

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `composer install` fails: "package not found" | VCS URL typo or private repo | Verify URL. For private: `composer config --global github-oauth.github.com <token>` |
| `vendor/bin/phpcs -i` doesn't show "DialedIn" | Composer plugin not allowed | Confirm `config.allow-plugins` in composer.json. Re-run `composer install`. |
| `phpcs` errors: "Referenced sniff does not exist" | Standards path not registered | `vendor/bin/phpcs --config-show` to inspect. Re-run `composer install`. |
| Psalm fails at parse: "Could not find directory" | `<ignoreFiles>` references missing path | Edit `psalm.xml.dist`, remove the missing entry. |
| `lefthook install` does nothing | Not in a git repo or `core.hooksPath` overridden | `git rev-parse --git-dir`; check `.git/config`. |
| Pre-commit hook doesn't run | `.git/hooks/pre-commit` missing/non-executable | Re-run `npx lefthook install`. On Windows, ensure LF line endings. |
| Gitleaks says "command not found" | Binary not installed locally | `brew install gitleaks`. Hook degrades but doesn't block. |
| PHPStan/Psalm out-of-memory | Large codebase | Capped at 1GB in scripts. Raise via `--memory-limit` if needed. |
| CI: "Dependencies lock file is not found" | Lockfile gitignored | Remove from `.gitignore`, commit. |
| Pre-push slow on legacy code | Full-project static analysis | Generate baselines (see "Post-install steps"). |

---

## Appendix: Manual installation

If `curl | bash` isn't an option (no internet at install time, restricted env, custom CI image, etc.), here's the manual equivalent of what the init script does. The script is the source of truth — refer to it (`bin/init`) for the canonical content.

### 1. `composer.json` — merge these keys

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/Dialed-In-Design/dialedin-coding-standards" }
    ],
    "require-dev": {
        "dialed-in-design/coding-standards": "^0.3"
    },
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true,
            "humanmade/psalm-plugin-wordpress": true
        }
    },
    "scripts": {
        "lint": "phpcs",
        "lint:fix": "phpcbf",
        "analyze": "phpstan analyse --memory-limit=1G",
        "taint": "psalm --threads=4",
        "check": ["@lint", "@analyze", "@taint"]
    }
}
```

### 2. `package.json` — merge these keys

```json
{
    "devDependencies": {
        "@dialed-in-design/coding-standards": "github:Dialed-In-Design/dialedin-coding-standards#v0.3.0",
        "eslint": "^8.57.0",
        "stylelint": "^16.9.0",
        "lefthook": "^1.7.0"
    },
    "scripts": {
        "prepare": "lefthook install || true",
        "lint:js": "eslint .",
        "lint:js:fix": "eslint . --fix",
        "lint:css": "stylelint \"**/*.{css,scss}\" --ignore-path .gitignore",
        "lint:css:fix": "stylelint \"**/*.{css,scss}\" --ignore-path .gitignore --fix"
    }
}
```

### 3. `phpcs.xml.dist`

```xml
<?xml version="1.0"?>
<ruleset name="Project">
    <file>.</file>
    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>*/node_modules/*</exclude-pattern>
    <arg name="colors"/>
    <arg value="ps"/>
    <rule ref="DialedIn"/>
</ruleset>
```

### 4. `phpstan.neon`

```neon
includes:
    - vendor/dialed-in-design/coding-standards/phpstan.neon.dist

parameters:
    paths:
        - wp-content/themes/your-theme
        - wp-content/plugins/your-custom-plugin
```

### 5. `psalm.xml.dist`

After `composer install`:
```bash
cp vendor/dialed-in-design/coding-standards/psalm.xml.dist psalm.xml.dist
```
Then edit `<projectFiles>` and remove `<ignoreFiles>` entries for paths not present.

### 6. `.eslintrc.json`

```json
{
    "extends": ["./node_modules/@dialed-in-design/coding-standards/.eslintrc.json"]
}
```

### 7. `stylelint.config.js`

```js
module.exports = {
    extends: ['@dialed-in-design/coding-standards/stylelint.config.js'],
};
```

### 8. `lefthook.yml`

```yaml
remotes:
  - git_url: https://github.com/Dialed-In-Design/dialedin-coding-standards
    ref: v0.3.0
    configs:
      - lefthook.yml
```

### Install

```bash
composer install
npm install
npx lefthook install
```

### Verify

```bash
vendor/bin/phpcs -i | grep DialedIn         # should match
vendor/bin/phpstan --version                # PHPStan 2.x
vendor/bin/psalm --version                  # Psalm 5.x
grep -q lefthook .git/hooks/pre-commit && echo "OK"
```
