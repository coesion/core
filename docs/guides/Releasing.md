# Releasing

Core uses a canonical `VERSION` file (plain semver `X.Y.Z`) and git tags in `vX.Y.Z` format.

## Version source of truth

- `VERSION` is authoritative.
- `classes/Core.php` must match `VERSION` (`Core::VERSION`).
- `dist/core.php` and artifact payload are generated from the same version source.
- `composer.json` must not contain a `version` field.

## When to release

Create a new release for substantial changes:

- bug fixes (`fix:`)
- new features (`feat:`)
- performance/refactor changes affecting behavior (`perf:`, `refactor:`)
- breaking changes (`!` or `BREAKING CHANGE:`)

Mapping:

- `patch`: fixes/perf/refactor/revert
- `minor`: features
- `major`: breaking changes

## Conventional commits

Release tooling parses commit messages since the last tag:

- `feat(scope): add x`
- `fix(scope): correct y`
- `refactor(scope): simplify z`
- `feat!: change API`
- body marker: `BREAKING CHANGE: ...`

## Commands

Plan next release:

```bash
composer release:plan
```

Cut release (auto bump inferred from commits):

```bash
composer release:cut
```

Force bump type:

```bash
composer release:cut -- patch
composer release:cut -- minor
composer release:cut -- major
```

Dry run preview:

```bash
composer release:cut -- --dry-run
```

Create release and push tag:

```bash
composer release:cut -- --push
```

Policy checks:

```bash
composer release:check
php tools/release-check.php --strict
```

## Changelog contract

Each release entry in `CHANGELOG.md` includes:

- `## vX.Y.Z - YYYY-MM-DD`
- `### Added`
- `### Changed`
- `### Fixed`
- `### Breaking`
- `### Upgrade Notes`

The changelog entry is a quick guide, not only a commit dump.

## CI enforcement

- `.github/workflows/tests.yml` runs `composer release:check`.
- `.github/workflows/release-policy.yml` runs strict policy on `main`/`master`/`develop` and release tags.
- On tag builds, version/tag/changelog must match.
