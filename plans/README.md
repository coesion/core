# Factory Artifact Plan Index

## Documents

- `00-overview.md`: goals, constraints, and final decisions.
- `01-release-architecture.md`: factory-to-artifact system design.
- `02-artifact-contracts.md`: PHP/JS artifact payload contracts.
- `03-ci-cd-workflows.md`: workflow behavior and publish logic.
- `04-versioning-policy.md`: independent PHP/JS version policy.
- `05-ops-runbook.md`: rollback/retry and incident playbooks.

## Decision Log

- 2026-02-14: Factory repo is source of truth; artifact repos are generated mirrors.
- 2026-02-14: Publish mode is fully automatic on semver tags.
- 2026-02-14: PHP and JS versioning are independent.
- 2026-02-14: Target repos are configured through `release-targets.json`.

## Plan Revision History

- v1 (2026-02-14): initial complete implementation plan and rollout runbook.
