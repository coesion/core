# 06 - Agentic Product Roadmap

## Summary
Execute product capabilities to make Core the most deterministic and productive PHP framework for coding agents, while preserving zero runtime dependencies.

## Phases

### Phase 0: Baseline + Cleanup
- Normalize workspace and capture pre-change metrics.
- Record test result baseline and deterministic audit hash.

### Phase 1: Core Agent Contracts
- Add `Introspect::contracts()` contract version map.
- Add deterministic snapshots for routes, schema tables, and model fields.
- Add `tools/agent-snapshot.php` with JSON/Markdown output and diff gate options.
- Add snapshot fixtures and CI script.

### Phase 2: Agent Productivity Primitives
- Add SQL composition helpers with minimal fluent operations.
- Add migration runtime (`Migration`) and CLI (`tools/migrate.php`) with status/apply/rollback/plan flows.
- Add deterministic tests for new behavior.

### Phase 3: Agent Testing + Interop
- Add `tests/support/AgentHttpHarness.php`.
- Refactor representative API/auth/route tests to use harness.
- Add in-repo PSR-like adapter interfaces and concrete adapters under `classes/Interop`.
- Add docs and compatibility tests.

## Acceptance
- `composer test` passes.
- `composer test-dist` passes.
- Snapshot checks are deterministic across repeated runs.
- Migration flows are idempotent and reversible.
- Contract changes are versioned and documented.
