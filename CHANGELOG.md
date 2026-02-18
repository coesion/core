# Changelog

## v1.4.0 - 2026-02-18

Quick guide:
- What changed: see Added/Changed/Fixed below.
- Impact: review Breaking and Upgrade Notes before deployment.

### Added
- add form handling and class scaffold codegen
- add security audit gate to test suite
- automate weekly proof and KPI cycle
- add contracts, snapshots, migrations, and interop tooling

### Changed
- allowlist codegen tool subprocess fixture
- align capability matrix with implemented features
- add dogfooding core principle to agent guide
- Allowlist intentional security-audit tool and fixture findings
- Align artifact token and release policy workflows
- Removed unknown entry in gitignore
- ignore sidetrack and sidecar local dirs
- add proof-driven guides, KPI cadence, and execution plans
- cover snapshots, migrations, SQL builder, and interop
- add v1.3.4 draft metadata

### Fixed
- None.

### Breaking
- None.

### Upgrade Notes
- No upgrade action required.

## v1.3.4 - 2026-02-16

Quick guide:
- What changed: see Added/Changed/Fixed below.
- Impact: review Breaking and Upgrade Notes before deployment.

### Added
- None.

### Changed
- resolve local stash merge conflicts
- Use only CORE_ARTIFACT_TOKEN for JS artifact workflow
- Prefer CORE_ARTIFACT_TOKEN for JS artifact publish
- Fix JS CI test discovery for Node test runner

### Fixed
- keep agent-audit JSON output clean on PHP 8.5
- handle off edge cases and forward triggerOnce args

### Breaking
- None.

### Upgrade Notes
- No upgrade action required.

## v1.3.3 - 2026-02-14

Quick guide:
- What changed: see Added/Changed/Fixed below.
- Impact: review Breaking and Upgrade Notes before deployment.

### Added
- None.

### Changed
- Automate artifact repo releases and token preflight checks
- Stage all generated files in release-cut
- Refresh PHP artifact snapshot after v1.3.2 release

### Fixed
- None.

### Breaking
- None.

### Upgrade Notes
- No upgrade action required.

## v1.3.2 - 2026-02-14

Quick guide:
- What changed: see Added/Changed/Fixed below.
- Impact: review Breaking and Upgrade Notes before deployment.

### Added
- None.

### Changed
- Fix monofile Error alias include in PHP artifact
- Refresh PHP artifact snapshot after v1.3.1 release

### Fixed
- None.

### Breaking
- None.

### Upgrade Notes
- No upgrade action required.

## v1.3.1 - 2026-02-14

Quick guide:
- What changed: see Added/Changed/Fixed below.
- Impact: review Breaking and Upgrade Notes before deployment.

### Added
- None.

### Changed
- Always mirror core release tag to core-php
- Refresh local artifact snapshots for v1.3.0

### Fixed
- None.

### Breaking
- None.

### Upgrade Notes
- No upgrade action required.

## v1.3.0 - 2026-02-14

Quick guide:
- What changed: see Added/Changed/Fixed below.
- Impact: review Breaking and Upgrade Notes before deployment.

### Added
- None.

### Changed
- Harden artifact release publish auth and sync
- Update release targets config
- Publish core-js to npm on release tags
- Fix mirror workflow auth and stability
- Add push-based PHP/JS artifact mirror workflow
- Rebuild JS monofile bundle and artifact core.js
- Apply pending framework updates and refresh release artifacts
- Make core-js artifact single-file bundle
- Add full dist/core.php PHPUnit lane for release validation
- Merge core.php output by namespace in build generator
- Add manifest-driven PHP/JS artifact publishing pipeline
- Update .gitignore
- expand contributing guidance for agentic coders
- rebuild README for agent-first positioning

### Fixed
- None.

### Breaking
- None.

### Upgrade Notes
- No upgrade action required.

## v1.2.0 - 2026-02-14

Quick guide:
- What changed: see Added/Changed/Fixed below.
- Impact: review Breaking and Upgrade Notes before deployment.

### Added
- deliver phase-a agentic observability contracts
- port core foundation, web, data, utilities, and api stack

### Changed
- add focused-evolution pillar and refresh agentic audit
- add audit report
- make C-PHIL-02 zero external runtime dependencies
- add CORE governance and CORE-EXTRA companion
- Add agent-optimized introspection, encryption, i18n, scheduling, and WebSocket support
- WSL support for win dev
- Fix release artifact workflow secret gating
- Fix syntax highlighting in static docs pages
- Add VERSION-driven release tooling and artifact publishing
- Rename Csrf class to uppercase CSRF
- update AGENTS instructions
- refine class overviews and fix docs renderer scope
- Add GitHub Pages docs publish workflow and static site builder
- Add router benchmark report tool
- Track AGENTS and MEMORY
- Optimize loop dispatcher hot path
- sync repo updates and tooling
- Guard ApiModuleTest definition
- Disable composer timeout
- Add CLI TUI demo and docs
- Add auth/security addon docs and examples
- Add tests and modernize for PHP 8.5
- Add API module classes and docs
- Optimize loop routing and docs
- Fix nullable callable deprecations
- Deduplicate class documentation
- Add loop-mode router compilation and tooling
- Update GitHub Actions workflows
- Uncomment test suite execution in PHP workflow
- Typos
- Remove AGENTS.md from repo
- Remove MEMORY.md from repo
- Update guide headings
- Initial commit

### Fixed
- None.

### Breaking
- None.

### Upgrade Notes
- No upgrade action required.

## 1.0.0

- Initial public release of Coesion/Core.
