# Why Core for Agents

Core is optimized for deterministic coding loops:

- Explicit static APIs that are fast to discover and execute.
- Machine-readable diagnostics and audit contracts.
- Deterministic snapshot tooling for CI drift detection.
- Zero external runtime dependencies for portable execution.

## Reproducible proof path

```bash
php tools/agent-audit.php --format=json --pretty
php tools/agent-snapshot.php --type=contracts --format=json --pretty
composer agent-snapshot-check
composer test
composer test-dist
```

## Claim contract

Every agent-oriented claim in this repository should include:
1. A runnable command.
2. An expected machine-readable output.
3. A verification timestamp or freshness check.
