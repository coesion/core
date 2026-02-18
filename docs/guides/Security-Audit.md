# Security Audit

Core includes a security audit gate in the test workflow.

## Commands

Run the full security suite:

```bash
composer test-security
```

Run dependency audit only:

```bash
composer audit-security-deps
```

Run code audit only:

```bash
composer audit-security-code
```

## What is checked

### 1) Dependency vulnerabilities

- Command used when available:
  - `composer audit --locked --no-interaction --format=plain --abandoned=report`
- Policy:
  - Vulnerabilities: blocking
  - Abandoned packages: reported, non-blocking

Note: if the local Composer binary does not support `audit`, the dependency audit command is skipped with an explicit message. CI uses `composer:v2`, where audit support is expected.

### 2) Code-level security patterns

`tools/security-audit.php` scans tracked PHP files under:

- `classes/`
- `tools/`
- `tests/`

Rules:

- `SEC001`: dangerous process execution calls (`exec`, `shell_exec`, `system`, `passthru`, `popen`, `proc_open`)
- `SEC002`: `unserialize()` without secure options requiring `allowed_classes => false`

Output format:

```text
RULE_ID path/to/file.php:line message
```

The command exits non-zero when non-allowlisted findings are present.

## Allowlist policy

Allowlist file: `tools/security-audit.allowlist.json`

Entry format:

```json
{
  "rule": "SEC001",
  "path": "tools/serve-docs.php",
  "line": 46,
  "reason": "Docs utility launches local commands."
}
```

Supported keys:

- `rule` (required)
- `path` (required, repo-relative)
- `reason` (required)
- `line` (optional)
- `line_range` (optional, `[start, end]`)

Guidelines:

- Allowlist only intentional, reviewed behavior.
- Keep reasons specific and actionable.
- Prefer narrowing by `line` or `line_range` for volatile files.

## CI integration

Security audit is enforced in:

- `.github/workflows/tests.yml`

Step:

- `Run security audit` executes `composer test-security` before release/artifact checks.
