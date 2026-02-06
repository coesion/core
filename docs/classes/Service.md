# Service


Overview:
`Service` is a lightweight service container supporting singleton and factory registrations.

Public API:
- `Service::register($serviceName, $factory)` registers a singleton factory.
- `Service::registerFactory($serviceName, $factory)` registers a non-singleton factory.
- `Service::serviceName(...$params)` resolves the service via `__callStatic`.

Example:
```php
Service::register('db', function () {
  return SQL::using('default');
});
$db = Service::db();
```

The Service module sports a service locator and a factory container.  

This module permits the user to register and retrieve a service manager instance, one (singleton) or multiple times.

### Register a service container (singleton)
---

You can use the `register` method to add a service container that will be called and instantiated a **single** time.  

```php
class Greeter {
  protected $name;
  public function __construct($name){ $this->name = $name; }
  public function hi(){ echo "Hello, {$this->name}"; }
}

Service::register('greeter',function() {
  return new Greeter("Friend");
});
```

You can now call the `greeter` service with the `Service::<service_name>` pattern :

```php
echo Service::greeter()->hi();
// Hello, Friend
```

Once instantiated the Service register holds the returned value from the passed callback and returns that every time the service is accessed.

### Passing arguments to the service init callback
---

In the precedent example the `Friend` parameter was hard-coded in the init callback.  
However if you want to let the user pass the init arguments the first time the service is invoked, you can : 

```php
Service::register('greeter',function($whoami) {
  return new Greeter($whoami);
});
```

```php
echo Service::greeter("John")->hi();
// Hello, John
echo Service::greeter("Marie")->hi();
// Hello, John
echo Service::greeter()->hi();
// Hello, John
```

### Register a service factory
---

You can use the `registerFactory` method to add a service container that will be called and instantiated **every** time.  

```php
Service::registerFactory('envelope',function($message) {
  return (object)[
    "type" => "envelope",
    "data" => [
      "body"   => $message,
      "length" => strlen($message),
    ],
  ];
});
```

Now, every time you call `Service::envelope` the init callback will be invoked

```php
$a = Service::envelope("This is a test");
$b = Service::envelope("It's over 9000!!!!");

echo json_encode([$a,$b], JSON_PRETTY_PRINT);
```

```json
[
    {
        "type": "envelope",
        "data": {
            "body": "This is a test",
            "length": 14
        }
    },
    {
        "type": "envelope",
        "data": {
            "body": "It's over 9000!!!!",
            "length": 18
        }
    }
]
```

The [Service](./Service.md) module sports a service locator and a factory container.  

This module permits the user to register and retrieve a service manager instance, one (singleton) or multiple times.

### Register a service container (singleton)
---

You can use the `register` method to add a service container that will be called and instantiated a **single** time.  

```php
class Greeter {
  protected $name;
  public function __construct($name){ $this->name = $name; }
  public function hi(){ echo "Hello, {$this->name}"; }
}

Service::register('greeter',function() {
  return new Greeter("Friend");
});
```

You can now call the `greeter` service with the `Service::<service_name>` pattern :

```php
echo Service::greeter()->hi();
// Hello, Friend
```

Once instantiated the Service register holds the returned value from the passed callback and returns that every time the service is accessed.

### Passing arguments to the service init callback
---

In the precedent example the `Friend` parameter was hard-coded in the init callback.  
However if you want to let the user pass the init arguments the first time the service is invoked, you can : 

```php
Service::register('greeter',function($whoami) {
  return new Greeter($whoami);
});
```

```php
echo Service::greeter("John")->hi();
// Hello, John
echo Service::greeter("Marie")->hi();
// Hello, John
echo Service::greeter()->hi();
// Hello, John
```

### Register a service factory
---

You can use the `registerFactory` method to add a service container that will be called and instantiated **every** time.  

```php
Service::registerFactory('envelope',function($message) {
  return (object)[
    "type" => "envelope",
    "data" => [
      "body"   => $message,
      "length" => strlen($message),
    ],
  ];
});
```

Now, every time you call `Service::envelope` the init callback will be invoked

```php
$a = Service::envelope("This is a test");
$b = Service::envelope("It's over 9000!!!!");

echo json_encode([$a,$b], JSON_PRETTY_PRINT);
```

```json
[
    {
        "type": "envelope",
        "data": {
            "body": "This is a test",
            "length": 14
        }
    },
    {
        "type": "envelope",
        "data": {
            "body": "It's over 9000!!!!",
            "length": 18
        }
    }
]
```



