# Agent Use Cases

Canonical coding-agent tasks validated for Core.

## 1) Add a new endpoint
- Define route.
- Dispatch via harness.
- Assert deterministic JSON payload.

Proof command:
```bash
vendor/bin/phpunit --filter AgentHttpHarnessTest
```

## 2) Apply and rollback schema change
- Register migration.
- Apply target.
- Roll back one step.

Proof command:
```bash
vendor/bin/phpunit --filter MigrationTest
```

## 3) Generate deterministic snapshots
- Snapshot contracts/routes/schema/models.
- Check baseline diff gate.

Proof command:
```bash
php tools/agent-snapshot.php --type=contracts --fail-on-diff=tests/fixtures/snapshots/contracts.json
```

## 4) Use lightweight SQL composition
- Build SELECT + filters + ordering + limit.
- Validate output rows and generated SQL.

Proof command:
```bash
vendor/bin/phpunit --filter SQLBuilderTest
```

## 5) Interop middleware pipeline
- Adapt request.
- Pass through middleware stack.
- Validate response adapter output.

Proof command:
```bash
vendor/bin/phpunit --filter InteropTest
```
