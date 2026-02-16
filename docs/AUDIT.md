# Core Agentic Framework Audit

> Date: 2026-02-14  
> Scope: Core vs Laravel, Symfony, Slim, and Mezzio  
> Goal: Make Core the best PHP framework for agentic coding workflows

---

## 1) Executive Summary

Core is currently the strongest option in this set for an agentic coding centered workflow when weighted for:
- deterministic APIs
- low runtime footprint
- zero external runtime dependencies
- introspection discoverability

The previous audit baseline was stale in important areas. This run confirms Core now includes built-in `i18n`, `Schedule`, `Crypt`, `WebSocket`, and a Redis cache adapter. Those categories are no longer "missing".

Core still lags in human-centric ecosystem parity (migrations, query builder ergonomics, testing helpers, PSR interoperability), and these now represent the main blockers to widening adoption without compromising its zero-dependency identity.

---

## 2) Methodology

### Rating Scale
- `Strong` = 4
- `Adequate` = 3
- `Basic` = 2
- `Missing` = 0

### Agentic Weighted Score Model (100 total)
- Introspection and self-discovery: 20
- Explicitness and deterministic behavior: 15
- Runtime footprint and startup profile: 15
- Automation ergonomics (non-interactive workflows): 15
- Security defaults for unattended execution: 10
- Zero dependency portability: 10
- Extensibility without hidden coupling: 10
- Ecosystem interoperability for agents: 5

### Evidence Rules
- Core claims are based on source and docs in this repository.
- Competitor claims are based on official documentation links listed in Section 10.

---

## 3) Corrections vs Previous Audit

| Category | Previous Status | Current Source-Backed Status | Core Evidence |
|---|---|---|---|
| i18n / Localization | Missing | Adequate | `classes/i18n.php`, `docs/classes/i18n.md` |
| Scheduling | Missing | Adequate | `classes/Schedule.php`, `docs/classes/Schedule.md` |
| Encryption | Missing | Adequate | `classes/Crypt.php`, `docs/classes/Crypt.md` |
| WebSocket / Real-time | Missing | Adequate | `classes/WebSocket.php`, `classes/WebSocket/Pusher.php`, `docs/classes/WebSocket.md` |
| Redis cache support | Missing | Adequate | `classes/Cache/Redis.php`, `docs/classes/Cache.md` |

---

## 4) Capability Matrix (Framework Breadth)

| Category | Core | Laravel | Symfony | Slim | Mezzio |
|---|---|---|---|---|---|
| Routing | Strong | Strong | Strong | Strong | Strong |
| HTTP Request/Response | Strong | Strong | Strong | Adequate | Adequate |
| Authentication | Adequate | Strong | Adequate | Missing | Missing |
| Authorization | Adequate | Strong | Strong | Missing | Missing |
| CSRF | Strong | Strong | Strong | Adequate | Adequate |
| Rate Limiting | Strong | Strong | Adequate | Missing | Missing |
| Security Headers | Strong | Basic | Basic | Missing | Missing |
| ORM / Database | Basic | Strong | Strong | Missing | Missing |
| Query Builder | Missing | Strong | Strong | Missing | Missing |
| Migrations | Missing | Strong | Strong | Missing | Missing |
| Caching | Adequate | Strong | Strong | Missing | Missing |
| Email | Adequate | Strong | Adequate | Missing | Missing |
| Template Engine | Basic | Strong | Strong | Missing | Missing |
| Validation | Adequate | Strong | Strong | Missing | Missing |
| File Storage | Adequate | Strong | Adequate | Missing | Missing |
| CLI / Console | Adequate | Strong | Strong | Missing | Missing |
| Queue / Jobs | Adequate | Strong | Strong | Missing | Missing |
| Events | Strong | Strong | Strong | Basic | Adequate |
| i18n / Localization | Adequate | Strong | Strong | Missing | Missing |
| Scheduling | Adequate | Strong | Adequate | Missing | Missing |
| Testing Utilities | Missing | Strong | Strong | Basic | Basic |
| DI Container | Basic | Strong | Strong | Adequate | Strong |
| Code Generation | Missing | Strong | Strong | Missing | Missing |
| WebSocket / Real-time | Adequate | Adequate | Adequate | Missing | Missing |
| PSR Compliance | Missing | Adequate | Strong | Strong | Strong |
| Middleware Pipeline | Basic | Strong | Strong | Strong | Strong |
| Form Handling | Missing | Adequate | Strong | Missing | Missing |
| Encryption | Adequate | Strong | Strong | Missing | Missing |
| API Resources | Adequate | Strong | Adequate | Missing | Missing |
| Content Negotiation | Strong | Basic | Adequate | Basic | Adequate |
| Performance / Footprint | Strong | Adequate | Adequate | Strong | Adequate |
| Zero External Runtime Dependencies | Strong | Missing | Missing | Missing | Missing |

Notes:
- Slim and Mezzio are intentionally minimalist and rely on composition of external packages for many categories.
- Core's "missing" ratings are mostly ecosystem parity categories, not runtime fundamentals.

---

## 5) Agentic Scorecard (Weighted)

| Criterion (Weight) | Core | Laravel | Symfony | Slim | Mezzio |
|---|---:|---:|---:|---:|---:|
| Introspection and self-discovery (20) | 4 | 2 | 3 | 1 | 1 |
| Explicitness and deterministic behavior (15) | 4 | 2 | 3 | 3 | 3 |
| Runtime footprint and startup profile (15) | 4 | 1 | 1 | 3 | 3 |
| Automation ergonomics (15) | 3 | 4 | 3 | 2 | 2 |
| Security defaults for unattended execution (10) | 3 | 4 | 4 | 2 | 2 |
| Zero dependency portability (10) | 4 | 0 | 0 | 1 | 1 |
| Extensibility without hidden coupling (10) | 3 | 3 | 3 | 3 | 3 |
| Ecosystem interoperability for agents (5) | 1 | 3 | 4 | 4 | 4 |

### Weighted Totals (max = 100)
- Core: **87.5**
- Symfony: **63.75**
- Laravel: **57.5**
- Slim: **55.0**
- Mezzio: **55.0**

Result: Core is currently rank #1 for this agentic-first model, but with clear parity risks in interop and developer automation.

---

## 6) Core Strengths for Agentic Coding

1. Small, explicit API surface with predictable static classes.
2. Zero external runtime dependency baseline (`composer.json` requires only PHP).
3. Introspection primitives already present (`Introspect::classes/methods/extensions/routes/capabilities`, `Schema::tables/describe`, `Model::schema/fields`).
4. Built-in machine-readable error modes (`Errors::JSON_VERBOSE`) support unattended execution diagnostics.
5. Single-file distributable (`dist/core.php`) and preload story favor deterministic environments.

---

## 7) Core Gaps Blocking Clear Dominance

1. Query ergonomics gap:
- `SQL` supports helper methods but no fluent builder for common CRUD patterns.

2. Schema lifecycle gap:
- No native migration workflow and no versioned schema evolution primitives.

3. Agent automation gap:
- Machine-readable audit contract exists (`tools/agent-audit.php`), but broader snapshot and case-study proof contracts are still expanding.

4. Interop gap:
- No first-class PSR-7/11/15 bridge layer, limiting plug-and-play with broader PHP middleware and tooling.

5. Test workflow gap:
- No built-in HTTP test harness and fixture utilities oriented to deterministic agent loops.

---

## 7.1) Proof Table (Reproducible Claims)

| Claim | Command | Expected Artifact | Last Verified |
|---|---|---|---|
| Audit contract is machine-readable | `php tools/agent-audit.php --format=json --pretty` | JSON payload with `schema_version/framework/capabilities/counts` | 2026-02-16 |
| Contract snapshot is deterministic | `php tools/agent-snapshot.php --type=contracts --fail-on-diff=tests/fixtures/snapshots/contracts.json` | Exit code `0` if unchanged | 2026-02-16 |
| Case-study output is machine-readable | `php tools/agent-case-study.php --preset=baseline --out=docs/guides/agent-case-study.baseline.json` | `docs/guides/agent-case-study.baseline.json` | 2026-02-16 |
| Proof freshness is enforceable | `composer proof-freshness-check` | JSON report with artifact age in days | 2026-02-16 |

---

## 8) 6-Month Roadmap (3 Phases, Zero Runtime Deps Hard Rule)

## Phase A (Weeks 1-8): Agent Observability and Determinism

### A1. Expand Introspection Coverage
- Add framework capability fields beyond PHP extension flags (routing mode, auth configured, cache driver loaded, schedule registrations).
- Acceptance:
- `Introspect::capabilities()` returns deterministic associative map with stable keys.
- New section in `docs/classes/Introspect.md` documents each key.
- Status: Implemented (2026-02-14)

### A2. Deterministic Machine-Readable Audit Export
- Add `tools/agent-audit.php` with `--format=json|md`.
- Acceptance:
- JSON output stable across runs with same source and config.
- Markdown export can regenerate `docs/AUDIT.md` sections.
- Status: Implemented (2026-02-14)

### A3. Error Envelope Stability Contract
- Standardize error payload shape and optional trace verbosity policy.
- Acceptance:
- `Errors::mode(Errors::JSON_VERBOSE)` contract documented with examples and field guarantees.
- Status: Implemented (2026-02-14)

## Phase B (Weeks 9-16): Agent Productivity Primitives

### B1. SQL Composition Helpers (Zero-Dep)
- Introduce lightweight composable helpers in `SQL` for common query patterns while preserving raw SQL escape hatch.
- Acceptance:
- Join/filter/order/limit helpers for common cases.
- No external packages introduced.

### B2. Route and Schema Snapshot Utilities
- Add deterministic exports:
- route snapshot
- table schema snapshot
- model field snapshot
- Acceptance:
- Snapshot diff is stable and suitable for CI checks.

### B3. Agent Test Harness Basics
- Add minimal HTTP dispatch test helpers and fixture bootstrap docs.
- Acceptance:
- At least 3 canonical agent task tests:
- new endpoint
- auth-protected endpoint
- schema change impact check

## Phase C (Weeks 17-24): Interop and Competitive Proof

### C1. PSR Bridge Layer (Optional, Internal)
- Provide internal adapter interfaces for PSR-style request/response/container semantics without adding runtime deps.
- Acceptance:
- Bridge docs and examples for external integration boundaries.

### C2. Agentic Benchmark Suite
- Add benchmark scenarios measuring:
- edit-to-green cycle time
- discovery steps needed
- failure recovery cost
- Acceptance:
- Reproducible benchmark report from `tools/` with documented methodology.

### C3. Publish Comparative Agentic TCO Section
- Add recurring section in `docs/AUDIT.md` showing effort metrics against competitors.
- Acceptance:
- Metrics updated with each audit run.
- Sources and methodology embedded in report.

---

## 9) Prioritized Backlog (Impact x Confidence / Cost)

| Item | Priority | Why |
|---|---|---|
| Expand `Introspect::capabilities()` | P0 | Highest leverage for autonomous decision-making. |
| Add `tools/agent-audit.php` JSON export | P0 | Enables machine-driven audits and CI checks. |
| Add SQL composition helpers | P1 | Reduces major friction without violating zero-dep rule. |
| Add route/schema/model snapshots | P1 | Makes regressions detectable by agents quickly. |
| Add minimal HTTP test harness | P1 | Improves agent reliability and shorter feedback loops. |
| Define PSR bridge docs/adapters | P2 | Improves ecosystem reach while preserving Core identity. |

---

## 10) Sources

### Core (repository evidence)
- `composer.json`
- `classes/Introspect.php`
- `classes/Schema.php`
- `classes/Model.php`
- `classes/Errors.php`
- `classes/i18n.php`
- `classes/Schedule.php`
- `classes/Crypt.php`
- `classes/WebSocket.php`
- `classes/Cache/Redis.php`
- `docs/classes/*.md`

### Laravel (official docs)
- Routing: https://laravel.com/docs/12.x/routing
- Service container: https://laravel.com/docs/12.x/container
- Migrations: https://laravel.com/docs/12.x/migrations
- Localization: https://laravel.com/docs/12.x/localization
- Scheduling: https://laravel.com/docs/12.x/scheduling
- Queues: https://laravel.com/docs/12.x/queues
- Testing: https://laravel.com/docs/12.x/testing
- Broadcasting: https://laravel.com/docs/12.x/broadcasting

### Symfony (official docs)
- Routing: https://symfony.com/doc/current/routing.html
- Service container: https://symfony.com/doc/current/service_container.html
- Security and CSRF: https://symfony.com/doc/current/security/csrf.html
- Messenger: https://symfony.com/doc/current/messenger.html
- Scheduler: https://symfony.com/doc/current/scheduler.html
- Translation: https://symfony.com/doc/current/translation.html
- Testing: https://symfony.com/doc/current/testing.html

### Slim (official docs)
- Docs root: https://www.slimframework.com/docs/v4/
- Routing: https://www.slimframework.com/docs/v4/objects/routing.html
- Middleware: https://www.slimframework.com/docs/v4/concepts/middleware.html
- Container: https://www.slimframework.com/docs/v4/concepts/di.html

### Mezzio (official docs)
- Docs root: https://docs.mezzio.dev/mezzio/
- Routing: https://docs.mezzio.dev/mezzio/v3/features/router/intro/
- Middleware: https://docs.mezzio.dev/mezzio/v3/features/middleware-types/
- Container: https://docs.mezzio.dev/mezzio/v3/features/container/intro/

---

## 11) Re-Audit Trigger Conditions

Run this audit again when one of these changes:
1. Core adds/removes capability classes or major runtime contracts.
2. Core adds compatibility bridges (for example PSR adapters).
3. Major competitor versions change architecture defaults.
4. Agent benchmark methodology changes.
