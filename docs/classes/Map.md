# Map


Overview:
`Map` is a dot-notation key-value store and is the data backbone for `Dictionary`.

Public API:
- `Map::all()` returns the array by reference.
- `Map::get($key, $default = null)` gets a value (and can set default).
- `Map::set($key, $value = null)` sets a value or merges an array.
- `Map::delete($key, $compact = true)` removes a value.
- `Map::exists($key)` checks existence.
- `Map::clear()` clears all values.
- `Map::load($fields)` loads from an array or object.
- `Map::merge($array, $merge_back = false)` merges with existing.
- `Map::compact()` removes null nodes.
- `Map::find($path, $create = false, ?callable $operation = null)` navigates by dot path.

Example:
```php
$map = new Map(['user' => ['name' => 'Ada']]);
$map->set('user.email', 'a@example.com');
```



