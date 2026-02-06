# Route Cache Extension

Overview:
This guide shows how to extend `Route` with a `cache()` helper using the `Module` trait. The extension stores serialized responses in `Cache` and serves them on subsequent requests.

Implementation:
```php
/*
 * Route Cache Extension
 */

Route::extend([
  // Add the `cache` method to the Route module
  'cache' => function ($expire = 0, $name = null) {
    // Make a unique identifier for this route.
    $key = 'route:' . ($name ?: md5(Request::URI()));

    // Install a before callback for checking if the cache is fresh
    $this->before(function () use ($expire, $key) {
      if (Cache::exists($key)) {
        // Handle OPTIONS before loading the Response
        Response::enableCORS();

        // Load cached response
        Response::load(Cache::get($key));

        // Refresh CORS header
        Response::enableCORS();
        Response::header('X-Cached', 'yes');

        // Returning false prevents the route callback from running
        return false;
      }

      // Cache miss, capture output after route runs
      $this->after(function () use ($expire, $key) {
        Cache::set($key, Response::save(), $expire);
      });
    });
  }
]);
```

Usage:
```php
Route::on('/users.json', function () {
  return SQL::all('SELECT * FROM users');
})->cache(3600);
```
