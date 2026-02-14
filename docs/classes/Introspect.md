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
- `Introspect::capabilities()` — deterministic capability map including extension flags and Core runtime metadata.

Capabilities contract:
- Existing extension keys remain available: `redis`, `sodium`, `curl`, `pdo`, `sqlite`, `mysql`, `mbstring`, `openssl`, `gd`, `zip`, `json`, `session`.
- The `core` key adds framework-level capabilities:
- `core.zero_runtime_dependencies` (bool)
- `core.runtime_dependency_count` (int)
- `core.introspection_available` (bool)
- `core.route.loop_mode` (bool)
- `core.route.loop_dispatcher` (string)
- `core.route.debug` (bool)
- `core.auth.booted` (bool)
- `core.cache.driver_loaded` (bool)
- `core.cache.driver` (string)
- `core.schedule.registered_jobs` (int)

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
// [
//   'redis' => false,
//   ...
//   'core' => [
//     'zero_runtime_dependencies' => true,
//     'runtime_dependency_count' => 0,
//     'route' => ['loop_mode' => false, 'loop_dispatcher' => 'fast', 'debug' => false],
//     'auth' => ['booted' => false],
//     'cache' => ['driver_loaded' => true, 'driver' => 'files'],
//     'schedule' => ['registered_jobs' => 0],
//   ],
// ]
```
