# SecurityHeaders

Overview:
`SecurityHeaders` applies secure default response headers.

Use `SecurityHeaders` to apply hardened response header defaults consistently across routes that need browser-level protection.

Public API:
- `SecurityHeaders::apply(array $overrides = [])`

Example:
```php
Auth::boot();

Route::get('/secure', function () {
  return 'ok';
})->secureHeaders();
```
