# Event


Overview:
`Event` is a thin wrapper around the `Events` trait, providing a global event bus.

Public API:
- `Event::on($name, callable $listener)` registers a listener.
- `Event::trigger($name, ...$args)` dispatches.
- `Event::single($name, callable $listener)` is a convenience for `onSingle`.

The Event module allow you to bind callbacks to custom events.

### Attach an handler
---

You can attach an handler to a named event via the `on` method.

```php
Event::on('myevent',function(){
   echo 'Hello, Friend!';
});
```

Multiple handlers can be attached to the event, they will be sequentially executed when the event will be triggered.

```php
Event::on('my.event',function(){
   echo 'First!';
});

Event::on('my.event',function(){
   echo 'Second!';
});
```
You can attach handlers to any event name.

### Trigger an event
---

You can trigger an event via the `trigger` method.

```php
Event::trigger('my.event');
```
The `trigger` method will return an array containing the return values of all the handler attached to the event.

**Example**

```php
Event::on('my.event',function(){
   return 'Hello!';
});

Event::on('my.event',function(){
   return time();
});

$results = Event::trigger('my.event');
```

The `$results` variable contains :

```php
array(2) {
 [0]  =>  string(6) "Hello!"
 [1]  =>  int(1389115191)
}
```

`NULL` will be returned if no handlers are attached to the event.

You can run a trigger only one time with the `triggerOnce` method.

### Passing parameters to event handlers
---

You can pass a variable number of parameter to event handlers appending them after the event name in the `trigger` method.

```php
Event::on('eat',function($who,$what,$where){
   echo "$who ate a $what, in the $where.";
});

Event::trigger('eat','Simon','Burrito','Kitchen');

// Result : Simon ate a Burrito, in the Kitchen
```

### Using the Events trait
---

You can augment any existing class adding the Events trait to them.
The Events trait has the same identically signature of the standard Event module (in fact the Event Module is a simple wrapper of the Events trait).

```php
class Game {
  use Events;
  public static function loadLevel($name){
    ...
    self::trigger("level.loaded", $name);
  }
}

Game::on("level.start",function($level_name){
  echo "Starting {$level_name}, BRING'EM'ON!!!\n";
});

Game::on("level.loaded",function($level_name){
  echo "Reticulating splines for {$level_name}...\n";
  Game::trigger("level.start", $level_name);
});

Game::on("level.start",function($level_name){
  echo "Replenish ammonitions...\n";
});

Game::loadLevel("E1M1");
```

```
Reticulating splines for E1M1...
Starting E1M1, BRING'EM'ON!!!
Replenish ammonitions...
```

