# CORE

## Document Status
- Last updated: 2026-02-14
- Maintainers: Core maintainers
- This is a living document. It does not use formal versioning.

## Normative Language
The key words `MUST`, `MUST NOT`, `SHOULD`, `SHOULD NOT`, and `MAY` are to be interpreted as requirement levels for this repository.

## Purpose
[C-PURPOSE-01] Core MUST remain a tools-first framework that gives explicit control and minimal assumptions to users and maintainers.

Example: route behavior is configured through `Options` and explicit `Route::compile()` in loop mode instead of hidden auto-detection.

## Base Philosophy
- [C-PHIL-01] Core MUST prefer explicit behavior over magic, including explicit boot, explicit config, and explicit dispatch.
- [C-PHIL-02] Core MUST keep zero external runtime dependencies in the main repository.
- [C-PHIL-03] Core MUST favor composable utilities over tightly coupled abstractions.
- [C-PHIL-04] Core SHOULD choose performance-aware defaults while keeping behavior understandable.
- [C-PHIL-05] Core MUST protect backward compatibility unless a change is clearly documented and justified.
- [C-PHIL-06] Core SHOULD optimize for predictable runtime behavior across environments.

Example: keep root package dependency-light and isolate benchmark dependencies under `benchmarks/`.

## Pillars
- [C-PILLAR-01] Predictability: APIs MUST behave consistently for equivalent inputs.
Example: route matching and dispatch order must remain stable once compiled.
- [C-PILLAR-02] Transparency: major behavior switches MUST be discoverable via documented options.
Example: `core.route.loop_mode` and related options are explicit configuration knobs.
- [C-PILLAR-03] Stability: public behavior SHOULD change incrementally and with migration guidance.
Example: evolving dispatch internals while preserving the `Route::dispatch()` contract.
- [C-PILLAR-04] Portability: code SHOULD run across supported PHP environments without platform-specific assumptions.
Example: avoid filesystem case assumptions that break autoload behavior.
- [C-PILLAR-05] Ergonomics without lock-in: helper APIs SHOULD reduce boilerplate without forcing one architecture.
Example: static class helpers available without requiring a container framework.
- [C-PILLAR-06] Focused evolution: Core MUST prioritize targeted additions over broad framework bloat.
Example: solve high-impact agent workflows with small primitives before introducing large subsystems.

## Coding Style Rules
- [C-STYLE-01] PHP files MUST start with `<?php` and a following blank line.
- [C-STYLE-02] PHP files MUST NOT close with `?>` unless they are templates.
- [C-STYLE-03] New code MUST NOT introduce `declare(strict_types=1);` in this codebase.
- [C-STYLE-04] Class names MUST use `PascalCase`; acronym classes MUST remain uppercase (`HTTP`, `URL`, `CLI`, `CSRF`).
- [C-STYLE-05] Method names MUST use `camelCase`.
- [C-STYLE-06] Public methods MUST include docblocks.
- [C-STYLE-07] Namespace and filename mapping MUST remain autoload-safe, especially for acronym class files on case-sensitive systems.
- [C-STYLE-08] Arrays SHOULD use short syntax `[]`.
- [C-STYLE-09] Braces SHOULD follow the established K&R style used in this repository.
- [C-STYLE-10] Contributors MUST preserve local naming and casing conventions unless there is a functional reason to change them.

Example: renaming `CSRF` without matching filename and loader expectations is not allowed.

## Tradeoffs We Intentionally Accept
- [C-TRADE-01] Core chooses simplicity over heavy abstraction.
- [C-TRADE-02] Core chooses static utility classes over mandatory deep DI frameworks.
- [C-TRADE-03] Core chooses explicit configuration over implicit auto-discovery.
- [C-TRADE-04] Core chooses compatibility and stability over frequent novelty.
- [C-TRADE-05] Core chooses focused extensibility over broad dependency-driven feature growth.

Example: introduce new behavior through existing classes and options before proposing new dependency chains.

## Do
- [C-DO-01] Reuse existing Core classes and extension points before adding new primitives.
- [C-DO-02] Keep changes scoped to the problem and verify no regressions in affected behavior.
- [C-DO-03] Add docs updates when behavior, options, or contracts change.
- [C-DO-04] Record non-obvious, high-value repository discoveries in `MEMORY.md`.
- [C-DO-05] Preserve public API contracts unless a deliberate compatibility plan is included.

## Don't
- [C-DONT-01] Do NOT add hidden behavior changes that are not observable in docs or options.
- [C-DONT-02] Do NOT add external dependencies to the main repository without strong architectural need.
- [C-DONT-03] Do NOT introduce style-only churn that harms blame history or readability.
- [C-DONT-04] Do NOT bypass loader and naming constraints that can break autoloading.
- [C-DONT-05] Do NOT merge changes that knowingly break existing features.

## Agent-First Execution Rules
- [C-AGENT-01] Agents MUST prefer non-mutating exploration before editing.
- [C-AGENT-02] Agents MUST prioritize existing Core classes over adding new subsystems.
- [C-AGENT-03] Agents MUST align tests to impact: core class behavior changes require stronger verification than docs-only edits.
- [C-AGENT-04] Agents MUST avoid introducing regressions and MUST surface unresolved risks.
- [C-AGENT-05] Agents MUST record unexpected, reusable facts in `MEMORY.md`.

Example: docs-only edits do not require full suite execution; main class changes do.

## Precedence and Conflict Resolution
- [C-PREC-01] `CORE.md` is the canonical top-level governance document.
- [C-PREC-02] Conflict resolution order is:
1. `CORE.md`
2. `AGENTS.md`
3. `CLAUDE.md`
4. Other repository documents
- [C-PREC-03] Lower-priority guidance MUST be interpreted through `CORE.md`.

## Paragraph IDs for Expansion
- [C-ID-01] Every normative rule in this document uses a stable paragraph ID.
- [C-ID-02] `CORE-EXTRA.md` MUST reference these IDs and MUST NOT introduce orphan policy.
- [C-ID-03] IDs SHOULD remain stable across edits; if semantics materially change, update references explicitly.

## Update Notes
- 2026-02-14: Initial living governance document created for Core philosophy, style, tradeoffs, and agent-first contribution rules.
- 2026-02-14: Added `C-PILLAR-06` to formalize targeted additions over framework bloat.
