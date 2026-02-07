# Auth

Overview:
`Auth` provides lightweight authentication helpers with session and bearer token support. It is designed to be extended through resolvers rather than imposing a user model.

Public API:
- `Auth::boot()`
- `Auth::resolver(callable $resolver)`
- `Auth::tokenResolver(callable $resolver)`
- `Auth::user()`
- `Auth::check()`
- `Auth::id()`
- `Auth::source()`
- `Auth::identity()`
- `Auth::login($identity)`
- `Auth::logout()`

Resolvers:
- `resolver($identity, $source, $context)`
- `tokenResolver($token, $payloadOrNull)`

Example:
```php
Auth::resolver(function ($identity, $source, $context) {
  if ($source === 'session') return User::find($identity);
  if ($source === 'bearer' && isset($identity->sub)) return User::find($identity->sub);
  return null;
});

Auth::boot();

Route::get('/profile', function () {
  return Auth::user();
})->auth();
```

Bearer/JWT:
- If `core.auth.jwt.secret` is set, `Token::decode` verifies signatures.
- `core.auth.jwt.require_exp` can enforce expiration checks.

