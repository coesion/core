# Loader


Overview:
`Loader` is a minimal class autoloader that resolves class names to file paths under registered roots.

Key behavior:
- Supports PSR-0 style underscore mapping.
- Automatically registers `classes/` at the end of the file.

Public API:
- `Loader::addPath($path, $name = null)` registers a root.
- `Loader::register()` installs SPL autoload.

Example:
```php
Loader::addPath(__DIR__ . '/src');
Loader::register();
```

The Loader module allow you easy enable class autoloading.

### Register the class loader
---

The Loader module automatically register itself by simply including the `Loader.php` file.

If you installed core via composer it's already registered upon `vendor/autoload.php` inclusion.

### Add a class path
---

The simplest way to retrieve a value from cache is via the `get` method.

```php
Loader::addPath('/path/to/my/classes');
```

