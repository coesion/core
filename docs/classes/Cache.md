# Cache


Overview:
`Cache` provides a multi-strategy cache facade. It selects a backend driver with a first-come, first-served strategy and exposes a consistent API for get/set/exists/increment operations.

Key behavior:
- Cache can be globally enabled or disabled with `Cache::enabled()`.
- Keys are hashed with `Cache::hash()` and can be grouped.
- Default value can be a callable and will be computed and stored if missing.

Public API:
- `Cache::using($driver)` registers the first valid driver from a string, list, or map of `driver => options`.
- `Cache::get($key, $default = '', $expire = 0)` returns cached value or resolves and stores default.
- `Cache::set($key, $value, $expire = 0)` writes a value.
- `Cache::delete($key)` removes a key.
- `Cache::exists($key)` checks existence, respecting `enabled`.
- `Cache::flush()` clears the underlying store.
- `Cache::inc($key, $value = 1)` increments a key.
- `Cache::dec($key, $value = 1)` decrements a key.
- `Cache::hash($key, $group = null)` returns a stable hash, with optional group prefix.

Events:
- Uses the `Events` trait, but `Cache` does not emit explicit events itself.

Example:
```php
Cache::using(['redis', 'files', 'memory']);
$user = Cache::get('user:42', function () {
    return User::load(42);
}, 300);
```

The Cache module allow you to handle an object persistence between requests and store heavy-computing persistent data.

### Retrieve a value
---

The simplest way to retrieve a value from cache is via the `get` method.

```php
$shares = Cache::get('shares');
```

You can optionally pass a default value and an expire time. The dafault value can be either a mixed or a callable. If you pass the latter it will invoked and the returned value will be used as the default value.

```php
$help = Cache::get('help',true);

$shares = Cache::get('shares',function(){
   return calc_user_shares();
});
```

**Important**: When the default value is used (from a cache miss), the cache value will be setted.

```php
$value_generator = function(){
	return time();
};

echo Cache::get('memento',$value_generator); // (Cache MISS) returned : 1389122001 ($value_generator called)
echo Cache::get('memento',$value_generator); // (Cache HIT) returned : 1389122001
echo Cache::get('memento',$value_generator); // (Cache HIT) returned : 1389122001

```

### Setting a value
---

You can set a value for a key with the `set` method.

```php
Cache::set('friend_or_foe','friend');
```

You can pass an optional `expiration` parameter in seconds.

```php
Cache::set('friend_or_foe','friend',15); // Expire in 15 sec

echo Cache::get('friend_or_foe'); // friend
sleep(20);
echo Cache::get('friend_or_foe'); // (no value)

```

### Delete a cached key
---

You can delete a cached key with the `delete` method.

```php
Cache::delete('friend_or_foe');
```

### Flush all cached keys
---

You can delete all cached keys with the `flush` method.

```php
Cache::flush();
```

### Check if a key is cached
---

You can delete a cached key with the `exists` method.

```php
if ( Cache::exists('user_info') ) { ... }
```

### Increment/Decrement a value
---

You can increment/decrement a cached key value with the `inc` and `dec` methods.

**Example**

```php
Event::on('user.login',function(){
	Cache::inc('user.logged');
});

Event::on('user.logout',function(){
	Cache::dec('user.logged');
});

```

Default inc/dec value is 1, you can however change it by passing the increment/decrement value as the second parameter of the inc/dec methods.

```php
Event::on('boss.killed',function(){
	Cache::inc('user.score',1000);
});
```

### Changing caching strategy/driver
---

You can choose the Cache driver via the `using` method. The optional second parameter is dictionary of init paramenters to pass to the selected driver.

The default driver is **Memory**, a request-only persistent storage.

**Example**

```php
// Use File-based caching
Cache::using('File',[
	'cache_dir' => __DIR__ . '/cache'
]);
```

### Enable/Disable cache
---

You can bybass all cache by passing `false` to the `Cache::enable` method.

```php
Cache::enabled(false);
```

A common use for this is for disabling cache in debug mode.

```php
Cache::enabled(!Options::get('debug',false));
```

