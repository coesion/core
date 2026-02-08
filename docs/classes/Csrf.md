# Csrf

Overview:
`Csrf` manages per-session CSRF tokens and verification.

Use `Csrf` on state-changing routes to verify request origin and prevent cross-site request forgery in browser-driven flows.

Public API:
- `Csrf::token()`
- `Csrf::verify($options = [])`
- `Csrf::rotate()`
- `Csrf::shouldVerify()`

Example:
```php
Auth::boot();

Route::post('/form', function () {
  return 'ok';
})->csrf();
```

Token usage:
```php
$token = Csrf::token();
// Send token via header or hidden input field.
```
