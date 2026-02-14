# Introspect

Overview:
`Introspect` provides runtime introspection for Core framework classes, methods, routes, and capabilities. It makes the framework self-describing so that agents can discover available functionality without reading source code.

Key behavior:
- Discovers all autoloaded Core classes, including traits and interfaces.
- Lists public methods including those added dynamically via `Module::extend()`.
- Enumerates registered routes with patterns, HTTP methods, and tags.
- Detects available PHP extensions and framework capabilities.

Public API:
- `Introspect::classes()` — all autoloaded Core classes.
- `Introspect::methods($class)` — public methods including Module-injected ones.
- `Introspect::extensions($class)` — only dynamically added methods.
- `Introspect::routes()` — all registered routes with patterns, methods, tags.
- `Introspect::capabilities()` — feature detection map (redis, sodium, curl, etc.).

Example:
```php
// List all Core classes
$classes = Introspect::classes();
// ['Cache', 'Check', 'Hash', 'Model', 'Route', 'SQL', ...]

// Discover methods on a class
$methods = Introspect::methods('Hash');
// ['can', 'extend', 'make', 'methods', 'murmur', 'random', 'uuid', 'verify', ...]

// See only runtime-extended methods
Hash::extend('customHash', function($data) { return md5($data); });
$extended = Introspect::extensions('Hash');
// ['customHash']

// List registered routes
$routes = Introspect::routes();
// [['pattern' => '/api/users', 'methods' => ['get'], 'tag' => 'users.list', 'dynamic' => false], ...]

// Check capabilities
$caps = Introspect::capabilities();
// ['redis' => false, 'sodium' => true, 'curl' => true, 'pdo' => true, ...]
```
