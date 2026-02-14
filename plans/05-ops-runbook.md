# 05 - Ops Runbook

## Standard Publish

```bash
composer release:check
composer release:check-artifacts
composer build-artifact-repo
php tools/build-js-artifact-repo.php
```

Then push release tag (`vX.Y.Z`) to trigger publish workflows.

## Retry After Failure

1. Fix failure cause (manifest, credentials, network, contract).
2. Re-run failed GitHub Action workflow.
3. Confirm artifact repo HEAD and tag align with factory tag.

## Rollback Procedure

1. Identify last known-good tag in artifact repo.
2. Reset artifact repo working branch to known-good commit.
3. Recreate/force tag to known-good release.
4. Trigger Packagist/npm resync if registry metadata drifted.

## Emergency Commands

```bash
php tools/release-check-artifacts.php --artifact=php --format=json
php tools/release-check-artifacts.php --artifact=js --format=json
php tools/release-check-artifacts.php --artifact=php --artifact-dir=dist/artifact
php tools/release-check-artifacts.php --artifact=js --artifact-dir=js/dist/artifact
```
