# Csrf

Overview:
`Csrf` manages per-session CSRF tokens and verification.

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

