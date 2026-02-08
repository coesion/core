# Model (abstract)

Overview:
`Model` is the base ORM class. It composes `Persistence` and `Relation` to provide active-record style behavior.

Use `Model` to represent persisted entities with active-record style querying, saving, and relation export.

Key behavior:
- Uses table and primary key from `Persistence` options.
- Supports exporting with relations without infinite recursion.

Public API:
- `Model::where($where_sql = false, $params = [], $flush = false)` returns instances matching criteria.
- `Model::count($where_sql = false, $params = [])` returns count.
- `Model::all($page = 1, $limit = -1)` returns all or a page.
- `Model::create($data)` creates and saves a new instance.
- `Model::load($pk)` loads a record by primary key (from `Persistence`).
- `Model::save()` persists the instance.
- `Model::export($transformer = null, $disabled_relations = [])` exports to array.
- `Model::primaryKey()` returns primary key value.

Example:
```php
class User extends Model { public $id, $name; }
$user = User::create(['name' => 'Ada']);
```

The Model class allow you to map database table to an object entity.

### Create model
---

```php
class User extends Model {
  // Define the target database table and primary key,
  // 'id' is the default.
  const _PRIMARY_KEY_ = "users.id"

  public $id,
         $email,
         $name;
}
```

### Retrieve all records
---

```php
$users = User::all();
```

The `all` method accept pagination arguments

```php
// Return page 2 with limit 20
$users = User::all(2, 20);
```

### Perform where clause
---
The `where` method specifies the WHERE fragment of a SQL query.

```php
// Return all users with gmail's email
$users = User::where("email LIKE '%@gmail.com'");
```

### Create new record
---

A new model can be created via the *ModelName*::`create` factory method.

```php
$mario = User::create([
  "name"  => "Mario",
  "email" => "mario.rossi@gmail.com"
]);
```

The resulting object implements an ActiveRecord pattern for accessing/modifying the model properties.

```
$mario->name = "Mario De Mario";
$mario->save();
```
