# Password


Overview:
`Password` wraps hashing and verification with a fallback for older PHP versions.

Public API:
- `Password::make($password)` hashes a password.
- `Password::verify($password, $hash)` verifies a password.
- `Password::compare($a, $b)` constant-time comparison.

Example:
```php
$hash = Password::make('secret');
$ok = Password::verify('secret', $hash);
```

The Password module allow you securely hash/verify password.

### Hash a password
---

```php
$hashed_passwd = Password::make('my_secret_password');
echo $hashed_passwd;
```
```
$2y$12$s88T0ByrVDPEILP2GfJUWeSqHUCFMWGFwx1XmyCguHmO2L20XuR3W
```

### Verify password
---

```php
var_dump(
  Password::verify('my_secret_password','$2y$12$s88T0ByrVDPEILP2GfJUWeSqHUCFMWGFwx1XmyCguHmO2L20XuR3W')
);
```
```
bool(true)
```

### Compare strings in a secure way
---

In order to prevent a [Timing Attack](https://en.wikipedia.org/wiki/Timing_attack), you can use the `compare` method for comparing string equality in a time-constant way.

```php
var_dump(
  Password::compare('my_secret_password','this-is-a-test')
);
```
```
bool(false)
```

