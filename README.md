# Dialed In Coding Standards

Centralised coding-standards configs for Dialed In Design WordPress projects.

This repo distributes six layers as one versioned bundle:

| Layer       | Purpose                                       | Format                              |
|-------------|-----------------------------------------------|-------------------------------------|
| PHPCS       | WordPress coding style + PHP compatibility    | `phpcs.xml.dist` (standard `DialedIn`) |
| PHPStan     | Static analysis at level 5 with WP rules      | `phpstan.neon.dist` (extendable)    |
| Psalm       | Taint analysis + type checking                | `psalm.xml.dist` (copy + adjust)    |
| ESLint      | JS/JSX/TS linting per WordPress conventions   | `.eslintrc.json` (extends)          |
| Stylelint   | CSS/SCSS linting per WordPress conventions    | `stylelint.config.js` (extends)     |
| Lefthook    | Local pre-commit + pre-push gates             | `lefthook.yml` (remote include)     |
| GH Actions  | Reusable CI workflow with all of the above    | `.github/workflows/code-quality.yml`|

PHPCS, PHPStan, Psalm, and JS/CSS tooling are also exposed through the reusable
GitHub Actions workflow, so a consumer can opt in to the full CI gate with a
single `uses:` reference.

## Targets

- **PHP** 8.2+
- **Node** 20 LTS+
- **WordPress** current (stubs from `php-stubs/wordpress-stubs`)
- Distribution: Composer (vcs repository) and npm (`github:` URL). No private registries.

## Installing in a downstream WordPress project

### 1. Composer side (PHPCS + PHPStan + Psalm)

Add to the consumer's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Dialed-In-Design/dialedin-coding-standards"
        }
    ],
    "require-dev": {
        "dialed-in-design/coding-standards": "^0.2"
    },
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true,
            "humanmade/psalm-plugin-wordpress": true
        }
    }
}
```

Always pin to a tag (e.g. `^0.2`), never `dev-main`.

### 2. npm side (ESLint + Stylelint)

```bash
npm install --save-dev \
  github:Dialed-In-Design/dialedin-coding-standards#v0.2.0 \
  eslint@^8.57.0 \
  stylelint@^16
```

### 3. Wire each layer's config in the consumer

#### PHPCS

Consumer creates `phpcs.xml.dist`:

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

The `DialedIn` standard is auto-registered by
`dealerdirect/phpcodesniffer-composer-installer` on `composer install`.

#### PHPStan

Consumer creates `phpstan.neon`:

```neon
includes:
    - vendor/dialed-in-design/coding-standards/phpstan.neon.dist

parameters:
    paths:
        - wp-content/themes/your-theme
        - wp-content/plugins/your-plugin
    # Optional: grandfather existing violations
    # baseline: phpstan-baseline.neon
```

Generate baseline:

```bash
vendor/bin/phpstan analyse --generate-baseline
```

#### Psalm

Psalm has no native config-include mechanism. **Copy** this repo's
`psalm.xml.dist` into the consumer and adjust the `<projectFiles>` directories
to point at your custom code. Keep the `<plugins>` and `runTaintAnalysis`
settings as-is.

Generate baseline:

```bash
vendor/bin/psalm --set-baseline=psalm-baseline.xml
```

#### ESLint

Consumer creates `.eslintrc.json`:

```json
{
    "extends": ["./node_modules/@dialed-in-design/coding-standards/.eslintrc.json"]
}
```

#### Stylelint

Consumer creates `stylelint.config.js`:

```js
module.exports = {
    extends: ['@dialed-in-design/coding-standards/stylelint.config.js'],
};
```

#### Lefthook

Consumer creates `lefthook.yml`:

```yaml
remotes:
  - git_url: https://github.com/Dialed-In-Design/dialedin-coding-standards
    ref: v0.2.0
    configs:
      - lefthook.yml
```

Install hooks:

```bash
npm install --save-dev lefthook
npx lefthook install
```

#### CI (reusable GitHub Actions workflow)

Consumer creates `.github/workflows/ci.yml`:

```yaml
name: CI
on: [push, pull_request]
jobs:
  code-quality:
    uses: Dialed-In-Design/dialedin-coding-standards/.github/workflows/code-quality.yml@v0.2.0
    with:
      php-version: "8.2"
      node-version: "20"
```

## Running locally

```bash
composer install
npm install

vendor/bin/phpcs                 # PHPCS
vendor/bin/phpcbf                # PHPCS auto-fix
vendor/bin/phpstan analyse       # PHPStan
vendor/bin/psalm                 # Psalm + taint
npx eslint .                     # ESLint
npx stylelint "**/*.{css,scss}"  # Stylelint
gitleaks detect --source .       # Secrets scan (requires gitleaks binary)
```

## Verifying

```bash
vendor/bin/phpcs -i              # should list "DialedIn"
vendor/bin/phpstan --version     # should report PHPStan 2.x
vendor/bin/psalm --version       # should report Psalm 5.x
```
