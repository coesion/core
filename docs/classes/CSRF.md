# CSRF

Overview:
`CSRF` manages per-session CSRF tokens and verification.

Use `CSRF` on state-changing routes to verify request origin and prevent cross-site request forgery in browser-driven flows.

Public API:
- `CSRF::token()`
- `CSRF::verify($options = [])`
- `CSRF::rotate()`
- `CSRF::shouldVerify()`

Example:
```php
Auth::boot();

Route::post('/form', function () {
  return 'ok';
})->csrf();
```

Token usage:
```php
$token = CSRF::token();
// Send token via header or hidden input field.
```
