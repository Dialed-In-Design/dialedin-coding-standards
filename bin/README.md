# `bin/init` — Project Initializer

Single-script onboarding for Dialed In coding standards in any WordPress project.

## Usage

From the project root:

```bash
curl -fsSL https://raw.githubusercontent.com/Dialed-In-Design/dialedin-coding-standards/v0.3.0/bin/init | bash
```

See [`docs/ONBOARDING.md`](../docs/ONBOARDING.md) for the full guide.

## What it does

1. Validates environment (PHP 8.2+, Node 20+, Composer, jq, git repo, clean tree).
2. Adds or merges 7 config files at the project root:
   - `composer.json` (merge — preserves existing deps)
   - `package.json` (merge — preserves existing deps and scripts)
   - `phpcs.xml.dist` (skip if exists)
   - `phpstan.neon` (skip if exists)
   - `.eslintrc.json` (skip if exists)
   - `stylelint.config.js` (skip if exists)
   - `lefthook.yml` (backup + write)
3. Runs `composer install`, `npm install`, `npx lefthook install`.
4. Copies `psalm.xml.dist` from `vendor/` after Composer install.
5. Verifies registration (`phpcs -i`, version checks, hook presence).

## Safety

- **Idempotent.** Safe to re-run. The VCS repo entry is added once. JSON merges via `jq` preserve existing keys.
- **Backups.** Any overwritten file gets a `*.bak.YYYYMMDD-HHMMSS` copy.
- **Skips conflicting configs.** If a project already has its own `phpcs.xml.dist` etc., the script tells you what to add manually rather than clobbering.
- **Pinned versions.** The script's `SCRIPT_TARGET_TAG` constant pins the standards version. The URL itself should also be tagged (e.g. `/v0.3.0/bin/init`) — never run from `main` in a client repo.

## Flags

```
--dry-run       Show what would happen; write nothing.
--version       Print initializer version and exit.
-h, --help      Show help.
```

Pass flags via `bash -s --` when piping:

```bash
curl -fsSL .../init | bash -s -- --dry-run
```

## Releasing a new version

When bumping the standards version (e.g. `v0.3.0` → `v0.4.0`):

1. Update three constants at the top of `bin/init`:
   - `SCRIPT_VERSION`
   - `SCRIPT_TARGET_TAG`
   - `COMPOSER_CONSTRAINT`
2. Update version references in `docs/ONBOARDING.md`.
3. Commit, tag (`git tag v0.4.0`), push the tag.
4. Create a GitHub Release for the tag with changelog notes.
5. Test the new tag's URL: `curl -fsSL .../v0.4.0/bin/init --version` should print `0.4.0`.
