# Migrations

`Migration` and `tools/migrate.php` provide deterministic schema lifecycle controls.

## Register migrations

```php
Migration::register('20260216_001_create_tasks', function () {
  SQL::exec('CREATE TABLE tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)');
}, function () {
  SQL::exec('DROP TABLE IF EXISTS tasks');
});
```

## Run

```bash
php tools/migrate.php --action=status --file=database/migrations.php
php tools/migrate.php --action=plan --file=database/migrations.php
php tools/migrate.php --action=apply --to=latest --file=database/migrations.php
php tools/migrate.php --action=rollback --steps=1 --file=database/migrations.php
```
