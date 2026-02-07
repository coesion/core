# RateLimiter

Overview:
`RateLimiter` provides a fixed-window limiter backed by `Cache` with a local fallback.

Public API:
- `RateLimiter::check(string $key, int $limit, int $window)`
- `RateLimiter::applyHeaders(int $limit, int $remaining, int $reset)`
- `RateLimiter::defaultKey()`

Example:
```php
Auth::boot();

Route::get('/api', function () {
  return ['ok' => true];
})->rateLimit(60, 60);
```

