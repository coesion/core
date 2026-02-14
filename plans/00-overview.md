# 00 - Overview

## Goal

Operate this repository as a factory monorepo and publish clean runtime artifacts to dedicated PHP and JS repositories.

## Success Criteria

- Manifest-driven targets (`release-targets.json`) control publish destinations.
- PHP and JS artifacts can publish independently from one tag event.
- Automatic publish uses strict pre-publish checks and deterministic payload creation.
- Operational runbooks define rollback and retry procedures.

## In Scope

- `/plans` architecture and operational documentation.
- Build and validation tools for PHP and JS artifact payloads.
- GitHub Actions release workflows for PHP and JS artifact mirrors.

## Out of Scope

- Organization/repository renaming.
- Runtime framework API redesign unrelated to packaging.
