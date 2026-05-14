# PR: Add `bin/init` for one-command project onboarding

## Suggested branch name

```
feat/bin-init-scaffolder
```

## Suggested commit message

```
feat: add bin/init for one-command project onboarding

Adds a `curl | bash`-installable scaffolder that drops all 7 config files
into any downstream WordPress project, runs installs, and verifies
registration.

- bin/init: idempotent bash script; merges JSON, backs up existing files,
  skips conflicting configs, runs composer/npm/lefthook installs, verifies.
- bin/README.md: usage, safety, release process.
- docs/ONBOARDING.md: restructured — curl one-liner is the headline; manual
  install moved to appendix.

The script pins all version references (composer constraint, npm package
ref, lefthook remote tag) to a single SCRIPT_TARGET_TAG constant so version
bumps are a one-line change.

Tested on:
- Empty git repo with no existing configs
- Repo with existing composer.json (yoast-seo dep) and package.json
  (webpack + @babel/core + custom lint:js script). Merges preserved all
  existing entries.
- curl | bash -s -- --dry-run invocation pattern.
```

## Suggested PR description

Adds `bin/init` so onboarding a new project becomes one command:

```bash
curl -fsSL https://raw.githubusercontent.com/Dialed-In-Design/dialedin-coding-standards/v0.3.0/bin/init | bash
```

### What's in the PR

| File | Purpose |
|---|---|
| `bin/init` | The scaffolder (~16 KB bash). |
| `bin/README.md` | Usage doc for the script itself. |
| `docs/ONBOARDING.md` | Restructured guide — curl is the headline path; manual install is the appendix. |

### What the script does (in order)

1. **Validates** PHP ≥ 8.2, Node ≥ 20, Composer, jq, git repo, working tree clean.
2. **Configures `composer.json`** — merges via `jq` if exists (preserves caller's deps), else creates fresh.
3. **Configures `package.json`** — same pattern; uses `//` operator so existing scripts aren't overwritten.
4. **Writes** `phpcs.xml.dist`, `phpstan.neon`, `.eslintrc.json`, `stylelint.config.js`, `lefthook.yml`. Skips any of these that already exist and prints the one-line edit the developer needs to make instead.
5. **Runs** `composer install && npm install && npx lefthook install`.
6. **Copies** `psalm.xml.dist` from `vendor/` (Psalm doesn't support includes).
7. **Verifies** `phpcs -i` shows `DialedIn`, `phpstan --version` and `psalm --version` succeed, pre-commit hook is installed. Exits non-zero if any check fails.

### Safety properties

- **Idempotent.** Re-running won't duplicate the VCS repo entry, won't overwrite without a timestamped backup, won't clobber a project's existing scripts.
- **Skip-on-conflict for non-JSON configs.** If `phpcs.xml.dist` already exists, the script prints `Add this rule to your existing config: <rule ref="DialedIn"/>` and moves on.
- **Non-interactive safe.** When stdin isn't a TTY (the curl-pipe case), the confirmation prompt for dirty working trees defaults to NO and aborts cleanly rather than auto-yessing a destructive prompt.
- **`--dry-run`** writes nothing; useful for previewing.
- **All versions pinned to one constant.** Bumping the standards version is a 3-line edit at the top of `bin/init`.

### Testing performed

| Scenario | Result |
|---|---|
| `bash -n` syntax check | ✓ |
| `--help` / `--version` | ✓ |
| Empty git repo (no configs) | ✓ creates all 7 files |
| Project with existing `composer.json` (yoast-seo) | ✓ merge preserves existing dep |
| Project with existing `package.json` (webpack + custom `lint:js`) | ✓ merge preserves existing dep + custom script |
| `curl ... \| bash -s -- --dry-run` invocation | ✓ flag passed through correctly |
| Re-run on already-scaffolded project | ✓ no duplicate VCS repo entries |
| Missing PHP / Node / jq | ✓ fails fast with install hint |

### Required follow-ups before merging

1. **Tag `v0.3.0`** on this repo after merge. The script's URL pattern (`/v0.3.0/bin/init`) requires the tag to exist.
2. **Create a GitHub Release** for `v0.3.0` with a changelog (the repo currently has no formal releases).
3. **Update the repo README** to point at `bin/init` as the canonical install path. (Existing manual install steps in the current README can stay or be replaced with a link to `docs/ONBOARDING.md`.)
4. **Pilot test** on one low-stakes Dialed In client repo before announcing internally.

### Things NOT in this PR (deliberately)

- **No `main`-pinned URL in any doc.** Every example uses `/v0.3.0/`. Running scaffolders from a moving branch is a footgun.
- **No `curl | sudo bash`.** The script never escalates privileges.
- **No telemetry.** The script doesn't phone home.
- **No `npm install -g`.** Everything is project-scoped via `devDependencies`.

### Open question

Worth deciding before merge: should we publish `bin/init` URLs at a stable redirect like `https://dialedin.ca/init` or a GitHub Pages-served alias, so the long raw.githubusercontent.com URL doesn't appear in every onboarding email? Optional, but improves the bus-factor (if the repo is ever moved/renamed, the alias is the only thing to update).
