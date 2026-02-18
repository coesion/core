<img src="https://github.com/coesion/core/blob/master/docs/assets/core-logo.png?raw=true" height="130">

---

# Core

**Agent-first PHP framework for deterministic coding loops.**

One-shot `core.php` runtime, zero external runtime dependencies, and high portability by design.

Proof-first CTA (run now):

```bash
php tools/agent-audit.php --format=json --pretty
php tools/agent-snapshot.php --type=contracts --format=json --pretty
composer agent-snapshot-check
composer proof-freshness-check
```

## Why Core for Agents

- Deterministic runtime contracts:
  - stable discovery through `Introspect::*`, `Schema::*`, `Model::schema()`, `Model::fields()`
- Machine-readable diagnostics:
  - structured errors via `Errors::JSON_VERBOSE`
- Deterministic environment audit:
  - `tools/agent-audit.php` with JSON/Markdown output and CI fail gates
- Explicit execution model:
  - configurable route loop mode and explicit `Route::compile()` / `Route::dispatch()`
- Portable by design:
  - single-file `core.php` + zero runtime package deps = predictable deploys anywhere

## 60-Second Agent Quick Start

### Option A: Composer artifact install

```bash
composer require coesion/core
```

```php
<?php

require __DIR__ . '/vendor/autoload.php';

Errors::capture(E_ALL);
Errors::mode(Errors::JSON_VERBOSE);

Route::get('/health', function () {
  return ['ok' => true, 'version' => Core::version()];
});

Route::dispatch('/health', 'get');
```

### Option B: One-shot runtime include (`core.php`)

```php
<?php

require __DIR__ . '/dist/core.php'; // or require __DIR__ . '/core.php' in artifact repo

Errors::capture(E_ALL);
Errors::mode(Errors::JSON_VERBOSE);
```

### First agent checks

```bash
php tools/agent-audit.php --format=json --pretty
php tools/agent-audit.php --fail-on-missing=capabilities.core.zero_runtime_dependencies
```

## Agent Contracts

| Concern | Contract |
|---|---|
| Capability discovery | `Introspect::capabilities()` |
| Class/method discovery | `Introspect::classes()`, `Introspect::methods()` |
| Route discovery | `Introspect::routes()` |
| Data shape discovery | `Schema::tables()`, `Schema::describe()`, `Model::schema()`, `Model::fields()` |
| Structured errors | `Errors::mode(Errors::JSON_VERBOSE)` |
| CI/runtime audit | `tools/agent-audit.php` |
| Contract versions | `Introspect::contracts()` |
| Snapshot exports | `tools/agent-snapshot.php` |

## Strength Snapshot

- Zero external runtime package dependencies (`composer.json` runtime requires only PHP).
- One-shot single-file load path via generated `dist/core.php`.
- Small, explicit API surface with static classes and predictable behavior.
- Built-in security and platform primitives:
  - `Auth`, `Gate`, `CSRF`, `RateLimiter`, `SecurityHeaders`, `Crypt`, `Token`
- Built-in agent-relevant utilities:
  - routing, SQL/Model, scheduling, i18n, queue/jobs, caching, negotiation, CSV/ZIP

For the evidence-based capability scorecard and roadmap, see `docs/AUDIT.md`.

## Build and Deploy

Build one-shot runtime artifact:

```bash
php tools/build-core.php
```

Build full artifact-repository payload:

```bash
composer build-artifact-repo
```

Run quality gates:

```bash
composer test
composer release:check
```

OPcache preload target:

- `dist/core.php`

## Docs

- Guides: `docs/guides/README.md`
- Class reference: `docs/classes/`
- Agentic audit and roadmap: `docs/AUDIT.md`
- Agent audit CLI guide: `docs/guides/Agentic-Audit.md`
- Agent snapshot CLI guide: `docs/guides/Agent-Snapshot.md`
- Agents quickstart: `docs/guides/Agents-Quickstart.md`
- Agent use-cases: `docs/guides/Agent-Use-Cases.md`
- Why Core for Agents: `docs/guides/Why-Core-for-Agents.md`
- Interop guide: `docs/guides/Interop.md`
- Migrations guide: `docs/guides/Migrations.md`
- Agent KPI cadence: `docs/guides/Agent-KPIs.md`
- Agent KPI log: `docs/guides/Agent-KPI-Log.md`
- Proof weekly template: `docs/guides/Proof-Weekly-Template.md`
- Distribution playbook: `docs/guides/Distribution-Playbook.md`
- Weekly automation command: `composer marketing-weekly-cycle` (supports `--github-fetch=1` with `GITHUB_TOKEN`)
- Security audit guide: `docs/guides/Security-Audit.md`
- Releasing: `docs/guides/Releasing.md`
- Artifact publishing: `docs/guides/Packagist-Artifact.md`

## Contributing

Core is maintained as a focused, agent-first framework: explicit behavior, deterministic contracts, and targeted evolution over framework bloat.

Agentic coders are first-class citizens in this project, and their code contributions are extremely welcome.

Best way for agents to contribute:
- Start with capability/discovery contracts first (`Introspect`, `Schema`, `Model`, `Errors`) and keep outputs deterministic.
- Prefer small, high-impact changes over broad subsystem additions.
- Preserve zero external runtime dependency posture.
- Add or update tests for behavioral changes, especially contract-level outputs.
- Update docs together with code (`README`, class docs, guides, `docs/AUDIT.md`) when behavior or contracts change.
- Keep release policy healthy by following conventional commit semantics and ensuring release checks pass.

Use:
- `docs/guides/Releasing.md`
- `docs/guides/Packagist-Artifact.md`

Founder contributor: Stefano Azzolini (@lastguest)

## License

MIT. See `LICENSE.md`.
