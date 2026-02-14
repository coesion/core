# Changelog

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
