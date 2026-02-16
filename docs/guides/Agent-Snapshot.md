# Agent Snapshot

`tools/agent-snapshot.php` exports deterministic snapshots for CI drift detection.

## Usage

```bash
php tools/agent-snapshot.php --type=contracts --format=json --pretty
php tools/agent-snapshot.php --type=routes --format=md
```

## Diff gates

```bash
php tools/agent-snapshot.php --type=contracts --fail-on-diff=tests/fixtures/snapshots/contracts.json
```

## Types
- `routes`
- `schema`
- `models`
- `capabilities`
- `contracts`
