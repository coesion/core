# Structure


Overview:
`Structure` wraps arrays or objects and allows property access (`$obj->key`) and array access interchangeably.

Public API:
- `new Structure($input = [], $deep = true)` creates a wrapper.
- `Structure::fetch($path, $root)` reads a dot-notation path.
- `Structure::create($class, $args = null)` instantiates a class with args.
- `Structure::canBeString($var)` checks if a value can be stringified.

Behavior:
- Nested arrays are automatically wrapped into `Structure` when `deep` is true.
- `offsetGet` calls callables stored in the map.

Example:
```php
$s = new Structure(['user' => ['name' => 'Ada']]);
echo $s->user->name;
```

