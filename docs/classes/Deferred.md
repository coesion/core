# Deferred

Overview:
`Deferred` executes a callback when the object is destroyed. This is a lightweight deferral mechanism for cleanup logic.

Use `Deferred` to guarantee cleanup actions run at scope end, such as releasing locks, flushing buffers, or writing final telemetry.

Public API:
- `new Deferred(callable $callback)` registers a callback.
- `Deferred::disarm()` disables the callback.
- `Deferred::prime()` re-enables the callback.

Example:
```php
$defer = new Deferred(function () {
  echo "cleanup";
});
```

The Deferred class ensure deferred execution of code, even in case of fatal error.

### Run code at function end or in case of error.
---

The passed callback will be queued for execution on Deferred object destruction.

```php
function duel(){
	echo "A: I will have the last word!\n";

	echo "B: Wanna bet?\n";

	$defer_B_last_word = new Deferred(function(){
		echo "B: Haha! Gotcha!\n";
	});
	
	die("A: I WIN!\n"); // Hahaha!

	echo "B: WUT?\n";
}

duel();
```

```
A: I will have the last word!
B: Wanna bet?
A: I WIN!
B: Haha! Gotcha!
```
