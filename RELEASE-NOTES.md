# v0.3.2 — Auto-baseline on install

## What's new

`bin/init` now automatically generates PHPStan and Psalm baselines after installing dependencies, and uncomments the `baseline:` line in `phpstan.neon`.

## Why

Pilot testing on OGP Stories revealed that requiring developers to manually generate baselines after running the init script was a step that gets forgotten. Forgetting it means the first `git push` after install fails with thousands of pre-existing violations, prompting developers to bypass hooks with `--no-verify`.

## Behavior

- **Legacy projects** (existing violations): baselines capture all current issues. Hooks then enforce only on new violations going forward.
- **Greenfield projects** (no violations): baselines are empty or near-empty. Functionally invisible.
- **Re-runs**: If baseline files already exist, generation is skipped with a refresh hint.
- **Failed installs**: If `vendor/bin/phpstan` or `vendor/bin/psalm` aren't present (e.g. composer install failed), baseline generation is gracefully skipped with manual instructions.

## Timing

Baseline generation runs `phpstan analyse --generate-baseline` and `psalm --set-baseline` on the full project. Expect 1-5 additional minutes on large codebases. The script reports violation counts when complete.

## Path adjustment workflow

If you adjust `phpstan.neon` paths or `psalm.xml.dist` `<projectFiles>` after the initial install, regenerate baselines:

```bash
rm phpstan-baseline.neon psalm-baseline.xml
vendor/bin/phpstan analyse --generate-baseline
vendor/bin/psalm --set-baseline=psalm-baseline.xml
```

The "Next steps" output now reminds you of this.

## Upgrade

```bash
curl -fsSL https://raw.githubusercontent.com/Dialed-In-Design/dialedin-coding-standards/v0.3.2/bin/init | bash
```

Idempotent. Safe to re-run on already-installed projects. Existing baseline files are not regenerated unless explicitly deleted first.

## Files changed

- `bin/init`: added "Generating baselines" section (~70 lines); updated "Next steps" output.

## No breaking changes

`^0.3` constraint still applies. No changes to standards, rulesets, or configurations.
