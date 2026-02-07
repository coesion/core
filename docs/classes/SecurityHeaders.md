# SecurityHeaders

Overview:
`SecurityHeaders` applies secure default response headers.

Public API:
- `SecurityHeaders::apply(array $overrides = [])`

Example:
```php
Auth::boot();

Route::get('/secure', function () {
  return 'ok';
})->secureHeaders();
```

