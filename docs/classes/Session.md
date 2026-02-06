# Session


Overview:
`Session` wraps PHP sessions and provides convenience methods for access and read-only access.

Key behavior:
- `Session::start()` ensures session is active.
- Session ID settings are hardened for PHP < 8.5.
- Session strict mode and cookie-only mode are enabled when supported.

Public API:
- `Session::start($name = null)` starts session.
- `Session::cookieParams($args = [])` gets or sets cookie parameters.
- `Session::name($name = null)` gets or sets session name.
- `Session::get($key, $default = null)` reads a value.
- `Session::set($key, $value = null)` sets a value.
- `Session::delete($key)` deletes a value.
- `Session::clear()` clears and destroys session.
- `Session::active()` checks if session is active.
- `Session::exists($key)` checks if key exists.
- `Session::readOnly()` returns a `SessionReadOnly` instance.

Supporting class:
- `SessionReadOnly` exposes safe accessors for views.

The Session module allow you to make hashes of variables.

### Create a new session

Start the session handler with the `Session::start` method.

```php
Session::start();
```

You can pass a custom SID string as parameter.

```php
Session::start("AWESOME_APP_SID");
```

### Close and clear session

All saved data and the session can be deleted with the `Session::clear` method.

```php
Session::clear();
```

### Retrieve a session value

You can retrieve a value from session stash via the `Session::get` method. An optional second parameter can be passed for a default value if the requested one is missing.

```php
$mydata = Session::get('mydata',"some default data");
```

### Set a session value

You can set a value into session stash via the `Session::set` method.

```php
$mydata = Session::get('my_options',[
  'a' => 1,
  'b' => 2,
]);

$mydata['a']++;

print_r( Session::set('my_options',$mydata) );
```

First run

```
Array
(
    [a] => 1
    [b] => 2
)
```

Second run

```
Array
(
    [a] => 2
    [b] => 2
)
```

### Check if a key is in session stash

You can check if a variable is in session stash with the `Session::exists` method.

```php
if(!Session::exists('user')) Redirect::to('/login');
```

The [Session](./Session.md) module allow you to make hashes of variables.

### Create a new session

Start the session handler with the `Session::start` method.

```php
Session::start();
```

You can pass a custom SID string as parameter.

```php
Session::start("AWESOME_APP_SID");
```

### Close and clear session

All saved data and the session can be deleted with the `Session::clear` method.

```php
Session::clear();
```

### Retrieve a session value

You can retrieve a value from session stash via the `Session::get` method. An optional second parameter can be passed for a default value if the requested one is missing.

```php
$mydata = Session::get('mydata',"some default data");
```

### Set a session value

You can set a value into session stash via the `Session::set` method.

```php
$mydata = Session::get('my_options',[
  'a' => 1,
  'b' => 2,
]);

$mydata['a']++;

print_r( Session::set('my_options',$mydata) );
```

First run

```
Array
(
    [a] => 1
    [b] => 2
)
```

Second run

```
Array
(
    [a] => 2
    [b] => 2
)
```

### Check if a key is in session stash

You can check if a variable is in session stash with the `Session::exists` method.

```php
if(!Session::exists('user')) Redirect::to('/login');
```



