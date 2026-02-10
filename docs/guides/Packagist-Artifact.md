# Packagist Artifact Publishing

This guide documents the split deployment model:

- `core-dev` repository: source code, tests, compiler (`tools/build-core.php`)
- artifact repository (`coesion/core`): generated runtime package with `core.php`

## Consumer install

Applications should install the artifact package:

```bash
composer require coesion/core
```

Then bootstrap only with:

```php
<?php

require __DIR__ . '/vendor/autoload.php';
```

No manual `require 'core.php'` is needed because the artifact package uses Composer `autoload.files`.

## Artifact package contract

The artifact repository must contain only runtime deliverables and package metadata:

- `core.php`
- `composer.json`
- `README.md`
- `LICENSE.md`
- optional `CHANGELOG.md`

`composer.json` in artifact repo must include:

```json
{
  "name": "coesion/core",
  "autoload": {
    "files": ["core.php"]
  }
}
```

## Include guard

Generated `core.php` includes a header guard:

- `if (defined('COESION_CORE_LOADED')) { return; }`
- `define('COESION_CORE_LOADED', true);`

This prevents duplicate parsing/redeclaration if included multiple times.

## Release automation from core-dev

Workflow file: `.github/workflows/release-artifact.yml`

Trigger:

- tag push matching `v*.*.*`

Pipeline:

1. install dependencies
2. build `dist/core.php`
3. build artifact payload in `dist/artifact`
4. lint and guard-test artifact `core.php`
5. push to artifact repository
6. apply the same tag in artifact repository
7. optional Packagist API notify

### Required GitHub secrets

- `CORE_ARTIFACT_REPO`: target repo in `owner/name` format (example: `coesion/core`)
- `CORE_ARTIFACT_TOKEN`: token with push/tag permission to artifact repo

### Optional secrets for Packagist API fallback

- `PACKAGIST_UPDATE_URL`
- `PACKAGIST_API_TOKEN`

Recommended default is Packagist webhook auto-update from the artifact repository.

## Packagist registration steps

1. Create artifact repository with valid root `composer.json`.
2. Push first tag (for example `v1.2.0`).
3. Sign in at Packagist and submit artifact repository URL.
4. Confirm package name is `coesion/core`.
5. Enable auto-update webhook (recommended).
6. Run install smoke test in a clean app:
   - `composer require coesion/core`
   - include `vendor/autoload.php`
   - verify classes are available without manual include.
