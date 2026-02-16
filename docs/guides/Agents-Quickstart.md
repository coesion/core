# Agents Quickstart

Run this flow to validate Core for coding-agent loops in under 10 minutes.

## 1) Install

```bash
composer require coesion/core
```

## 2) Run deterministic checks

```bash
php tools/agent-audit.php --format=json --pretty
php tools/agent-snapshot.php --type=contracts --format=json --pretty
composer agent-snapshot-check
```

## 3) Run quality gates

```bash
composer test
composer test-dist
```

## 4) Evaluate proof freshness

```bash
composer proof-freshness-check
```

## Expected artifacts
- Audit JSON contract from `tools/agent-audit.php`.
- Snapshot JSON contract from `tools/agent-snapshot.php`.
- Freshness JSON report from `tools/proof-freshness-check.php`.
