# Dialed In Coding Standards

Centralised coding-standards configs for Dialed In Design WordPress projects.

This repo distributes a single PHPCS ruleset (more layers — PHPStan, Psalm,
ESLint, Stylelint — coming in later steps).

## What's in the PHPCS ruleset

- **WordPress-Extra** (Core + Extra; `WordPress-Docs` intentionally omitted).
- **PHPCompatibilityWP** with `testVersion = 8.2-`.

## Installing in a downstream repo

This package is distributed via Git, not Packagist. In the consuming repo's
`composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/dialed-in-design/coding-standards"
        }
    ],
    "require-dev": {
        "dialed-in-design/coding-standards": "^0.1"
    },
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true
        }
    }
}
```

Always pin to a tag (e.g. `^0.1`), never to `dev-main`.

Then in the downstream repo, create a `phpcs.xml.dist` that references the
`DialedIn` standard registered by this package:

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

The `dealerdirect/phpcodesniffer-composer-installer` plugin registers this
package's ruleset with PHPCS automatically on `composer install`, so
`DialedIn` resolves without any `--standard=path/to/...` argument.

## Running

```bash
composer install
vendor/bin/phpcs           # lint
vendor/bin/phpcbf          # auto-fix what's safe
```

## Verifying the install

```bash
vendor/bin/phpcs --version
vendor/bin/phpcs -i        # should list "DialedIn" alongside WordPress, PHPCompatibilityWP, etc.
```
