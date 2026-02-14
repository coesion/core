# NPM Artifact Publishing

This guide documents JS artifact publishing from the factory repository to a clean artifact repository.

## Package source of truth

- source repo: factory monorepo
- artifact repo: configured via `release-targets.json` -> `artifacts.js.repo`
- package name: configured via `release-targets.json` -> `artifacts.js.package_name`
- version source: `js/VERSION` and `js/package.json` must match

## Artifact payload contract

`js/dist/artifact` must include runtime deliverables only:

- `package.json`
- `core.js`
- `README.md`
- `LICENSE.md`
- optional `CHANGELOG.md`

The artifact must not include source-only directories or files (`src/`, `tests/`, `scripts/`, `index.js`).

## Build and validate locally

```bash
npm --prefix js run test
npm --prefix js run build
php tools/build-js-artifact-repo.php
php tools/release-check-artifacts.php --artifact=js --artifact-dir=js/dist/artifact
```

`npm --prefix js run build` generates `js/dist/core.js` as a single-file bundle consumed directly by the artifact payload.

## CI publish flow

Workflow: `.github/workflows/release-artifact-js.yml`

1. validate manifest
2. run JS tests/build
3. build/validate `js/dist/artifact`
4. mirror publish to JS artifact repo
5. apply same tag in artifact repo
6. publish `js/dist/artifact` to npm (skips when version already exists)

## Secrets

- `CORE_JS_ARTIFACT_TOKEN`: token with push/tag access to configured JS artifact repo
- `NPM_TOKEN`: npm automation token with publish permission for `@coesion/core-js`

## Registry sync

NPM publish is handled directly by `.github/workflows/release-artifact-js.yml` on release tags.
If metadata drifts, retag and rerun the release workflow.
