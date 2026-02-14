# 04 - Versioning Policy

## Independent Versions

- PHP version authority: `VERSION` and `classes/Core.php`.
- JS version authority: `js/VERSION` and `js/package.json`.

## Tag Behavior

- Shared tag triggers both workflows.
- Each workflow decides publish/no-publish based on changed source paths and generated payload diff.

## Change Detection

PHP-relevant paths:

- `classes/**`
- `tools/build-core.php`
- `tools/build-artifact-repo.php`
- `tools/release-check-artifacts.php`
- `release-targets.json`

JS-relevant paths:

- `js/src/**`
- `js/scripts/**`
- `js/package.json`
- `js/VERSION`
- `tools/build-js-artifact-repo.php`
- `tools/release-check-artifacts.php`
- `release-targets.json`
