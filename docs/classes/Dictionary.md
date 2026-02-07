# Dictionary (abstract)


Overview:
`Dictionary` is a static key-value repository with dot-notation access built on `Map`.

Key behavior:
- Uses a static `Map` instance per subclass.
- Can load or merge arrays and access nested paths.

Public API:
- `::all()` returns all values by reference.
- `::get($key, $default = null)` retrieves value by dot path.
- `::set($key, $value = null)` sets values.
- `::delete($key, $compact = true)` removes a path.
- `::exists($key)` checks existence.
- `::clear()` clears all values.
- `::load($fields)` replaces the map.
- `::merge(array $array, $merge_back = false)` merges values.

Example:
```php
class AppConfig extends Dictionary {}
AppConfig::set('db.host', 'localhost');
$host = AppConfig::get('db.host');
```

The Dictionary class defines a multi-level key value repository with dot notation keys accessors.

### Building a dictionary
---

Dictionary is a behaviour class, it must be extended by another class or the value repository will be shared.

```php
class Config extends Dictionary {}
```

### Setting a value
---

You can set a value from a key path via the `get` method.

A valid key path is a arbitrary deep sequence of `.` separated strings.

**Examples**

- `test`
- `alpha.beta`
- `pages.section.text_block.3`

```php
Config::set('options.use_cache',false);

Config::set('users.whitelist',[
	'frank.ciccio',
	'walter.submarine',
	'pepen.spacca',
]);
```

### Getting a value
---

You can get a value from a key path via the `get` method.

```php
echo Config::get('users.whitelist.1'); // walter.submarine
```
You can optionally pass a default value to be returned when the requested key is not found. If a callable is passed the returned value will be used.

```php
print_r( Config::get('a.test',['b'=>123]) ); // Array( [b] => 123 )
echo Config::get('a.test.b'); // 123
```

### Getting all values
---

You can get all key-values as an associative array via the `all` method.

```php
$all_data = Config::all();
```

Results :

```php
Array (
    [users] => Array (
        [whitelist] => Array(
            [0] => frank.ciccio
            [1] => walter.submarine
            [2] => pepen.spacca
        )
    )
)
```
### Clearing the dictionary
---

You can clear all values from a dictionary via the `clear` method.

```php
Config::clear();
```

### Merging data
---
The `merge` method extends the dictionary with values passed via an associative array. The second optional parameter will define the if merge data from right-to-left or backwise (default is false = left-to-right ).

**Setting initial data**

```php
Config::clear();
Config::merge([
    'user' => [
        'name' => 'Simon',
        'role' => 'Villain',
    ],
]);
```

```php
Array (
    [user] => Array (
            [name] => Simon
            [role] => Villain
        )
)
```

**Standard merge (left-to-right)**

```php
Config::merge([
    'user' => [
        'name' => 'Frank',
    ],
    'happy' => true,
]);
```

```php
Array (
    [user] => Array (
            [name] => Frank
            [role] => Villain
        )
    [happy] => 1
)
```
**Back merge (right-to-left)**

```php
Config::merge([
    'user' => [
        'name' => 'Frank',
    ],
    'happy' => true,
],true);
```

```php
Array (
    [user] => Array (
            [name] => Simon
            [role] => Villain
        )
    [happy] => 1
)
```

### Getting multiple values
---

You can retrieve multiple values, minimizing function calls by passing an associative array of type :

```
DESTINATION_KEY => DICTIONARY_PATH
```

**Example** :

```
MyService::init([
    'username'   =>  Config::get('aws.username'),
    'password'   =>  Config::get('aws.password'),
    'from'       =>  Config::get('user.email'),
    'verbose'    =>  Config::get('app.global.debug'),
]);
```

Can be written with a single call to `get` as :

```
MyService::init( Config::get([
    'username'   =>  'aws.username',
    'password'   =>  'aws.password',
    'from'       =>  'user.email',
    'verbose'    =>  'app.global.debug',
]));
```

