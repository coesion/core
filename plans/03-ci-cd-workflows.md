# 03 - CI/CD Workflows

## Release Triggers

- Tag push matching `v*.*.*`
- Manual `workflow_dispatch`

## PHP Workflow

1. Validate release target manifest and export env from `tools/release-check-artifacts.php`.
2. Run `composer install`.
3. Build and validate `dist/artifact`.
4. Detect relevant changes since previous tag.
5. Mirror payload to configured artifact repo and force-sync release tag.

## JS Workflow

1. Validate release target manifest and export env from `tools/release-check-artifacts.php`.
2. Run Node tests and JS bundle build.
3. Build and validate `js/dist/artifact`.
4. Detect relevant JS changes since previous tag.
5. Mirror payload to configured JS artifact repo and force-sync release tag.

## Failure Matrix

- Manifest invalid -> fail before any publish action.
- Token missing -> fail with explicit secret name requirement.
- Push failure -> workflow fails; rerun after credential/network fix.
- No source changes -> workflow exits cleanly without publish.
