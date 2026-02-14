# Schema

Overview:
`Schema` provides database schema introspection, allowing agents and application code to discover table structures, column types, and keys at runtime without reading source code.

Key behavior:
- Works with both SQLite and MySQL via automatic driver detection.
- Accepts either a Model class name or a raw table name.
- Results are cached internally; call `Schema::flush()` to clear.

Public API:
- `Schema::describe($modelOrTable)` — returns array of column descriptors with name, type, nullable, default, key.
- `Schema::tables()` — lists all tables in the current database.
- `Schema::columns($modelOrTable)` — returns flat array of column name strings.
- `Schema::hasTable($table)` — checks if a table exists.
- `Schema::flush()` — clears the internal schema cache.

Example:
```php
// Describe a table directly
$columns = Schema::describe('users');
// [
//   ['name' => 'id', 'type' => 'integer', 'nullable' => false, 'default' => null, 'key' => 'PRI'],
//   ['name' => 'email', 'type' => 'text', 'nullable' => true, 'default' => null, 'key' => ''],
// ]

// List all tables
$tables = Schema::tables();
// ['users', 'posts', 'comments']

// Via a Model subclass
class User extends Model {
    public $id, $name, $email;
}
User::persistOn('users');

$fields = User::fields();
// ['id', 'name', 'email']

$schema = User::schema();
// Full column descriptors
```
