# CORE-EXTRA

## Relationship to CORE.md
This document is a non-normative expansion companion to `CORE.md`. It explains intent, examples, and migration context. It never overrides `CORE.md`.

## How to Read This File
- Every section references one paragraph ID from `CORE.md`.
- The source policy in `CORE.md` is authoritative.
- If an expansion appears to conflict with source policy, follow `CORE.md`.

## Expansions

### [C-PURPOSE-01] Tools-first, explicit control
Source paragraph: `CORE.md` `[C-PURPOSE-01]`

Why this exists:
Core is intended as a collection of building blocks, not a framework that silently dictates architecture.

Examples:
- Compose routing, model, and response helpers directly.
- Configure runtime behavior through `Options`.

Counterexamples:
- Auto-running implicit setup based on hidden global state.

Migration notes:
When adding convenience APIs, ensure they remain opt-in and explicit.

### [C-PHIL-03] Minimize external dependencies
Source paragraph: `CORE.md` `[C-PHIL-03]`

Why this exists:
Dependency minimization reduces upgrade risk, installation friction, and transitive security surface.

Examples:
- Keep benchmark-specific packages isolated under `benchmarks/`.
- Reuse existing classes instead of adding package wrappers.

Counterexamples:
- Adding a new package for a small helper that can be implemented in-house.

Migration notes:
When dependency pressure appears, prototype with existing primitives first and document the gap.

### [C-PHIL-05] Backward compatibility discipline
Source paragraph: `CORE.md` `[C-PHIL-05]`

Why this exists:
Core is used as a base layer, so breaking changes have outsized downstream cost.

Examples:
- Preserve method contracts while optimizing internals.
- Document compatibility impact when behavior must change.

Counterexamples:
- Silent response shape changes in helpers relied on by existing integrations.

Migration notes:
Include compatibility notes in relevant docs whenever behavior changes.

### [C-PILLAR-02] Transparency through options
Source paragraph: `CORE.md` `[C-PILLAR-02]`

Why this exists:
Operators and agents need a reliable way to understand what behavior is active.

Examples:
- Route behavior toggles are named and documented under explicit option keys.

Counterexamples:
- Runtime side effects based on undeclared environment conditions.

Migration notes:
New toggles should be documented at introduction time.

### [C-STYLE-06] Public method docblocks
Source paragraph: `CORE.md` `[C-STYLE-06]`

Why this exists:
Static classes expose broad APIs; docblocks provide fast, low-friction discoverability.

Examples:
- Document parameters, return intent, and side effects for each public method.

Counterexamples:
- Adding public methods with no contract hints.

Migration notes:
Backfill docblocks when touching nearby public APIs.

### [C-STYLE-07] Loader-safe naming and files
Source paragraph: `CORE.md` `[C-STYLE-07]`

Why this exists:
Autoload behavior depends on class/file mapping; case mismatches can break on some filesystems.

Examples:
- `CSRF` class in `classes/CSRF.php`.

Counterexamples:
- Renaming class casing without renaming its file.

Migration notes:
Treat class renames as code + filename + loader compatibility work.

### [C-TRADE-02] Static utility classes over mandatory DI
Source paragraph: `CORE.md` `[C-TRADE-02]`

Why this exists:
Core prioritizes low ceremony and direct call ergonomics.

Examples:
- Use existing static utility APIs for common concerns.

Counterexamples:
- Requiring container wiring for simple helper usage.

Migration notes:
Prefer optional adapters over mandatory container rewrites.

### [C-DO-02] Scope changes and verify regressions
Source paragraph: `CORE.md` `[C-DO-02]`

Why this exists:
Small, targeted changes lower risk and improve review quality.

Examples:
- Restrict edits to affected classes/docs.
- Run verification that matches impact.

Counterexamples:
- Bundling unrelated refactors into functional fixes.

Migration notes:
Split mixed changes into separate pull requests when practical.

### [C-DONT-02] No dependency creep in root
Source paragraph: `CORE.md` `[C-DONT-02]`

Why this exists:
Root dependency growth weakens Core's portability and maintenance profile.

Examples:
- Keep optional tooling isolated outside runtime-critical paths.

Counterexamples:
- Adding runtime package requirements to solve local tooling issues.

Migration notes:
Evaluate whether tooling can live in sub-apps or scripts first.

### [C-AGENT-03] Test effort aligned to impact
Source paragraph: `CORE.md` `[C-AGENT-03]`

Why this exists:
Agent workflows need deterministic effort allocation: stronger checks for behavior changes, lighter checks for docs-only work.

Examples:
- Core class mutation: run meaningful tests for affected behavior.
- Docs-only edit: no full suite required by default.

Counterexamples:
- Skipping validation after changing runtime behavior.

Migration notes:
When in doubt, err toward additional targeted checks.

### [C-PREC-01] CORE.md is canonical
Source paragraph: `CORE.md` `[C-PREC-01]`

Why this exists:
A single highest-precedence reference avoids ambiguous instruction resolution.

Examples:
- Resolve conflicts by applying the precedence order in `CORE.md`.

Counterexamples:
- Choosing rules ad hoc across multiple guidance docs.

Migration notes:
Update lower-priority docs when new canonical policy is introduced.

### [C-ID-02] No orphan policy in expansions
Source paragraph: `CORE.md` `[C-ID-02]`

Why this exists:
Expansions must stay explanatory, not become a second conflicting policy source.

Examples:
- New narrative section includes an existing source ID.

Counterexamples:
- Adding new requirements only in `CORE-EXTRA.md`.

Migration notes:
If new policy is needed, add it to `CORE.md` first, then expand here.

## Maintenance Rule
Any long-form rationale added to this file must reference an existing paragraph ID from `CORE.md`. Orphan expansion content is not allowed.
