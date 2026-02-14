# 02 - Artifact Contracts

## PHP Artifact Contract (`dist/artifact`)

Required files:

- `core.php`
- `composer.json`
- `README.md`
- `LICENSE.md`

Rules:

- `composer.json.name` equals `release-targets.json` -> `artifacts.php.package_name`
- `composer.json.autoload.files` includes `core.php`
- payload must be runtime-only (no tests/tooling)

## JS Artifact Contract (`js/dist/artifact`)

Required files:

- `package.json`
- `index.js`
- `core.js`
- `src/index.js`
- `README.md`
- `LICENSE.md`

Rules:

- `package.json.name` equals `artifacts.js.package_name`
- `package.json.version` equals `js/VERSION`
- exports must define `.` and `./bundle`
- no `tests/` or `scripts/` directory in artifact payload

## Manifest Schema

`release-targets.json`:

- `defaults.tag_pattern`
- `defaults.publish_mode`
- `artifacts.php.repo`
- `artifacts.php.branch`
- `artifacts.php.package_name`
- `artifacts.php.homepage`
- `artifacts.js.repo`
- `artifacts.js.branch`
- `artifacts.js.package_name`
- `artifacts.js.homepage`
- `artifacts.js.registry`
