# Migration

Overview:
`Migration` provides deterministic schema migration registration and execution.

Public API:
- `Migration::register($id, callable $up, ?callable $down = null)`
- `Migration::status()`
- `Migration::plan($to = 'latest')`
- `Migration::apply($to = 'latest')`
- `Migration::rollback($steps = 1)`
- `Migration::flush()`

Notes:
- Migration ids are sorted lexicographically.
- Applied migrations are tracked in `core_migrations`.
- Rollback executes `down` callback when provided.

CLI:
```bash
php tools/migrate.php --action=status
php tools/migrate.php --action=apply --to=latest --file=database/migrations.php
php tools/migrate.php --action=rollback --steps=1 --file=database/migrations.php
```
