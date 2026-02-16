# Agentic Audit

`tools/agent-audit.php` exports deterministic framework diagnostics for agent workflows and CI checks.

## Usage

```bash
php tools/agent-audit.php --format=json --pretty
php tools/agent-audit.php --format=md
```

## Options

- `--format=json|md` output format (`json` default)
- `--pretty` pretty JSON output
- `--fail-on-missing=<dot.path>` fail if a field is missing/falsy (repeatable, comma-separated)
- `--help` show usage

## CI Gate Example

```bash
php tools/agent-audit.php --format=json --fail-on-missing=capabilities.core.zero_runtime_dependencies
```

This exits with code `1` when the requested contract path is missing or false.

Pair it with snapshots:

```bash
php tools/agent-snapshot.php --type=contracts --format=json --pretty
php tools/agent-snapshot.php --type=contracts --fail-on-diff=tests/fixtures/snapshots/contracts.json
```

## Output Contract

Top-level fields:
- `schema_version`
- `framework`
- `capabilities`
- `counts`

The capabilities payload includes both:
- Extension flags (`redis`, `pdo`, `openssl`, ...)
- Framework-level metadata under `capabilities.core` (`route`, `auth`, `cache`, `schedule`, and dependency posture)
