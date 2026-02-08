# Gate

Overview:
`Gate` is a minimal authorization layer with named abilities.

Use `Gate` to model authorization abilities like `admin`, `editor`, or resource-specific policies and enforce them at route boundaries.

Public API:
- `Gate::define($ability, callable $callback)`
- `Gate::allows($ability, ...$args)`
- `Gate::authorize($ability, ...$args)`

Example:
```php
Gate::define('admin', function ($user) {
  return $user && $user->role === 'admin';
});

Route::get('/admin', function () {
  return 'ok';
})->auth()->can('admin');
```
