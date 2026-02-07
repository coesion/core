# Options


Overview:
`Options` is a configuration dictionary with loaders for PHP, INI, JSON, arrays, and `.env` files.

Public API:
- `Options::loadPHP($filepath, $prefix_path = null)`
- `Options::loadINI($filepath, $prefix_path = null)`
- `Options::loadJSON($filepath, $prefix_path = null)`
- `Options::loadArray(array $array, $prefix_path = null)`
- `Options::loadENV($dir, $envname = '.env', $prefix_path = null)`

Filters:
- Loader filters `load.php`, `load.ini`, `load.json`, `load.array`, `load.env`, and `load` are supported.

Example:
```php
Options::loadENV(__DIR__);
$dsn = Options::get('db.dsn');
```

The Options module exposes functions to manage a data-value dictionary loading values from various formats.

See Dictionary.

### Loading a config file

You can load a config tree from a file or an array via the utility loaders methods : 

| Method | Description |
|--------|-------------|
| `loadArray` | Load directly an array of key->values |
| `loadPHP`   | Load array key->values from a PHP file returning it. |
| `loadINI`   | Load values from an `.ini` file. |
| `loadJSON`  | Load JSON key->value map. |
| `loadENV`   | Load environment variables from a .env file. |

#### Loading options from file or array

```php
Options::loadPHP('config.php');
```

**config.php**

```php
<?php
return [
  "debug" => false,
  "cache" => [
    "enabled" => true,
  	"driver"  => "files",
  	"path"    => "/tmp/cache", 
  ], 
];
```

#### Loading Options and Environment from a .env file

```php
Options::loadENV($dir,$envname='.env',$prefix_path=null)
```

**/index.php**

```php
Options::loadENV(__DIR__);

print_r( Options::all() );
```

**/.env**

```bash
# This is a comment
BASE_DIR="/var/webroot/project-root"
CACHE_DIR="${BASE_DIR}/cache"
TMP_DIR="${BASE_DIR}/tmp"
```

**Result:**

```php
Array
(
    [BASE_DIR] => /var/webroot/project-root
    [CACHE_DIR] => /var/webroot/project-root/cache
    [TMP_DIR] => /var/webroot/project-root/tmp
)
```

The [Options](./Options.md) module exposes functions to manage a data-value dictionary loading values from various formats.

See [Dictionary](./Dictionary.md).

