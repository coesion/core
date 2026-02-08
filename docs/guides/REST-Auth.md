# REST API With Auth

This guide shows a common REST API setup using the new Auth helpers.

## Setup
```php
// Configure JWT verification (optional but recommended for APIs).
Options::set('core.auth.jwt.secret', $_ENV['APP_JWT_SECRET'] ?? 'dev-secret');
Options::set('core.auth.jwt.require_exp', true);

// Optional defaults for security add-ons.
Options::set('core.security.rate_limit.enabled', true);
Options::set('core.security.rate_limit.key', 'ip:route');

// Boot route helpers.
Auth::boot();
```

## User Resolver
```php
Auth::resolver(function ($identity, $source) {
  // Session: identity might be a user id stored via Auth::login().
  if ($source === 'session') {
    return User::find($identity);
  }

  // Bearer/JWT: identity is payload when JWT is valid.
  if ($source === 'bearer' && is_object($identity) && isset($identity->sub)) {
    return User::find($identity->sub);
  }

  return null;
});
```

## Authorization Rules
```php
Gate::define('users.read', function ($user) {
  return $user && $user->role === 'admin';
});
```

## Login (Session or JWT)
```php
Route::post('/login', function () {
  $email = Request::input('email');
  $pass = Request::input('password');

  $user = User::where("email = ?", [$email])->first();
  if (!$user || !Password::verify($pass, $user->password)) {
    Response::error(401, 'Invalid credentials');
    return ['error' => 'Invalid credentials'];
  }

  // Session-based login.
  Auth::login($user->id);

  // JWT-based login (for API clients).
  $jwt = Token::encode([
    'sub' => $user->id,
    'exp' => time() + 3600,
  ], Options::get('core.auth.jwt.secret'));

  return ['token' => $jwt];
})->secureHeaders()->rateLimit(10, 60);
```

## Protected REST Routes
```php
Route::group('/api', function () {
  Route::get('/users', function () {
    return User::all();
  })->auth()->can('users.read')->rateLimit(60, 60)->secureHeaders();

  Route::get('/users/:id', function ($id) {
    return User::find($id);
  })->auth()->rateLimit(120, 60)->secureHeaders();

  Route::post('/users', function () {
    $data = (array)Request::data();
    return User::create($data);
  })->auth()->rateLimit(30, 60)->secureHeaders();
});
```

## API::resource With Auth
The `API::resource()` helper registers routes internally, so attach auth at the group level.

```php
Route::group('/api', function () {
  API::resource('/articles', [
    'class' => 'Article',
    'sql' => [
      'table' => 'articles',
      'primary_key' => 'id',
    ],
  ]);

  API::resource('/users', [
    'class' => 'UserResource',
    'sql' => [
      'table' => 'users',
      'primary_key' => 'id',
    ],
  ]);
})
->before(function () {
  // Auth gate for all /api resources
  if (!Auth::check()) {
    Response::error(401, 'Unauthorized');
    Response::add('Unauthorized');
    return false;
  }

  // Optional rate limiting for all /api resources
  if (Options::get('core.security.rate_limit.enabled', true)) {
    $result = RateLimiter::check(RateLimiter::defaultKey(), 120, 60);
    RateLimiter::applyHeaders(120, $result['remaining'], $result['reset']);
    if (!$result['allowed']) {
      Response::error(429, 'Too Many Requests');
      Response::add('Too Many Requests');
      return false;
    }
  }

  SecurityHeaders::apply();
});
```

## API::resource With Per-Resource Abilities
If you need per-resource authorization, you can wrap each resource in its own group and use `Gate`.

```php
Gate::define('articles.read', function ($user) {
  return $user && $user->role === 'editor';
});

Gate::define('users.read', function ($user) {
  return $user && $user->role === 'admin';
});

Route::group('/api/articles', function () {
  API::resource('/', [
    'class' => 'Article',
    'sql' => [
      'table' => 'articles',
      'primary_key' => 'id',
    ],
  ]);
})
->before(function () {
  if (!Auth::check() || !Gate::allows('articles.read')) {
    Response::error(403, 'Forbidden');
    Response::add('Forbidden');
    return false;
  }
  SecurityHeaders::apply();
});

Route::group('/api/users', function () {
  API::resource('/', [
    'class' => 'UserResource',
    'sql' => [
      'table' => 'users',
      'primary_key' => 'id',
    ],
  ]);
})
->before(function () {
  if (!Auth::check() || !Gate::allows('users.read')) {
    Response::error(403, 'Forbidden');
    Response::add('Forbidden');
    return false;
  }
  SecurityHeaders::apply();
});
```

## CSRF (Session Clients Only)
For browser-based clients that use session auth, enable CSRF protection on write routes:
```php
Route::post('/account', function () {
  return ['ok' => true];
})->auth()->csrf()->secureHeaders();
```

CSRF tokens can be read with:
```php
$token = CSRF::token();
```

Send the token via `X-CSRF-Token` header or `_csrf` input.
