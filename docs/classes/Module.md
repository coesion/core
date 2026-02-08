# Module (trait)

Overview:
`Module` allows runtime extension of classes with new methods using closures.

Use the `Module` trait when you need runtime extension points, allowing modules to be augmented with new methods without editing class source.

Public API:
- `::extend($method, $callback = null)` registers new methods.
- Magic `__call` and `__callStatic` dispatch to extensions.

Example:
```php
Text::extend('shout', function ($s) { return strtoupper($s) . '!'; });
echo Text::shout('hello');
```

The Module trait provides a way to extend classes, even static with new methods.

### Extend a class with new methods
---

```php
class Test {
  use Module;
  public static function Foo(){ echo "Foo"; }
}

Test::Foo(); // Foo
Test::Bar(); // Fatal error: Call to undefined method Test::Bar

Test::extend([
  'Bar' => function(){ echo "Bar"; },
]);

Test::Bar(); // Bar

```
