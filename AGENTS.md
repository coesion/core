# Core Framework — Agent Reference

## Why Core is Agent-Optimal

- **Single-file dist** (~185KB / ~50K tokens) — fits in one context window
- **Zero external dependencies** — no composer install, no network fetches
- **71+ static classes** — predictable API surface, no hidden magic
- **Explicit boot** — `require 'core.php'` or `require 'classes/Loader.php'`, then configure
- **No framework scaffold** — no CLI generators, no directory conventions to memorize

## Quick Start

```php
require 'classes/Loader.php';           // Autoload all classes
SQL::connect('sqlite:app.db');          // Connect database
Errors::capture(E_ALL);                 // Enable error handling
Errors::mode(Errors::JSON_VERBOSE);     // Structured errors for agents
```

## Introspection API

```php
Schema::tables();                       // List all database tables
Schema::describe('users');              // Column names, types, keys, defaults
Model::schema();                        // Schema for any Model subclass
Model::fields();                        // Flat column name array

Introspect::classes();                  // All loaded Core classes
Introspect::methods('Route');           // Public methods including extensions
Introspect::extensions('Route');        // Only dynamically added methods
Introspect::routes();                   // All registered routes
Introspect::capabilities();             // Feature detection map
```

## Machine-Readable Errors

```php
Errors::mode(Errors::JSON_VERBOSE);
// Output: {"error":"...","type":"...","code":0,"file":"...","line":42,"trace":[...]}
```

## Class Reference

| Class | Purpose |
|-------|---------|
| `API` | REST API response helpers |
| `Auth` | Authentication guard |
| `Cache` | Multi-driver cache (Files, Memory, Redis) |
| `Check` | Data validation rules |
| `CLI` | Command-line interface tools |
| `Collection` | Fluent array wrapper |
| `Core` | Framework metadata and version |
| `Crypt` | Symmetric encryption (sodium) |
| `CSRF` | Cross-site request forgery protection |
| `CSV` | CSV read/write |
| `Deferred` | Deferred execution callbacks |
| `Dictionary` | Abstract key-value store with dot notation |
| `Email` | Multi-driver email sending |
| `Error` | Error class alias |
| `Errors` | Error/exception handler with JSON modes |
| `Event` | Global event emitter |
| `File` | Filesystem operations |
| `Filter` | Global filter/hook system |
| `Gate` | Authorization policies |
| `Hash` | Hashing (md5, sha, murmur, UUID) |
| `HTTP` | cURL-based HTTP client |
| `i18n` | Internationalization and translations |
| `Introspect` | Runtime class and route introspection |
| `Job` | Database-backed job queue |
| `Loader` | PSR-0 style autoloader |
| `Map` | Nested array with dot-notation access |
| `Message` | Flash message session store |
| `Model` | Abstract ORM base class |
| `Negotiation` | Content negotiation |
| `Options` | Configuration store (PHP, JSON, ENV, INI) |
| `Password` | Bcrypt password hashing |
| `RateLimiter` | Request rate limiting |
| `Redirect` | HTTP redirect helpers |
| `Request` | HTTP request accessor |
| `Resource` | RESTful resource routing |
| `Response` | HTTP response builder |
| `REST` | REST controller base |
| `Route` | URL router and dispatcher |
| `Schema` | Database schema introspection |
| `Schedule` | Cron-based task scheduling |
| `SecurityHeaders` | Security header helpers |
| `Service` | Service container |
| `Session` | Session management |
| `Shell` | Shell command execution |
| `SQL` | PDO database abstraction |
| `Structure` | Deep array/object accessor |
| `Text` | String utilities and templating |
| `Token` | JWT encode/decode |
| `URL` | URL parsing and manipulation |
| `View` | Template rendering |
| `WebSocket` | WebSocket messaging facade |
| `Work` | Parallel task execution |
| `ZIP` | ZIP archive operations |

## Common Patterns

### Model CRUD
```php
class User extends Model {
    public $id, $name, $email;
}
User::persistOn('users');

$user = User::create(['name' => 'Alice', 'email' => 'a@b.com']);
$user = User::load(1);
$user->name = 'Bob';
$user->save();
$users = User::where('active = ?', [1]);
```

### Routing
```php
Route::get('/api/users', function() {
    Response::json(User::all());
});
Route::get('/api/users/:id', function($id) {
    Response::json(User::load($id));
});
Route::post('/api/users', function() {
    Response::json(User::create(Request::data()));
});
Route::dispatch(Request::URI(), Request::method());
```

### Caching
```php
Cache::using(['redis', 'files', 'memory']);
$data = Cache::get('key', function() {
    return SQL::each("SELECT * FROM expensive_query");
}, 3600);
```

### Encryption
```php
$key = Crypt::key();
$encrypted = Crypt::encrypt('secret data', $key);
$plain = Crypt::decrypt($encrypted, $key);
```

### Scheduling
```php
Schedule::register('cleanup', '0 2 * * *', 'db.cleanup');
Schedule::register('reports', '0 9 * * 1', 'email.weekly');
// In cron runner:
Schedule::run();
```

### Internationalization
```php
i18n::load('en', 'lang/en.json');
i18n::locale('en');
echo i18n::t('user.welcome', ['name' => 'Alice']);
```
