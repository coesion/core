# Code Generation

`tools/codegen.php` provides deterministic class scaffolding for agent workflows.

## Command

```bash
php tools/codegen.php --type=class --name=MyClass [--namespace=App\\Domain] [--root=/path] [--force] [--format=json|md]
```

## Generated files

For `--type=class --name=MyClass`:
- `classes/MyClass.php`
- `docs/classes/MyClass.md`
- `tests/MyClassTest.php`

## Behavior

- Validates class names strictly (`PascalCase` style).
- Optional namespace declaration can be added to generated class.
- Existing files are skipped unless `--force` is passed.
- Emits structured result output for automation:
  - `result.created`
  - `result.skipped`
  - `result.errors`

## Examples

```bash
php tools/codegen.php --type=class --name=Report
```

```bash
php tools/codegen.php --type=class --name=Report --namespace=App\\Domain --format=md
```

```bash
php tools/codegen.php --type=class --name=Report --force
```
