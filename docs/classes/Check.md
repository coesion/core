# Check


Overview:
`Check` validates a data map against named validation rules. You can define custom rules at runtime, and it ships with a standard set (required, email, min, max, etc.).

Key behavior:
- `Check::valid()` executes rules and collects errors.
- Each rule can have parameters, using `rule:param1,param2` syntax.
- Error messages are templated using `Text::render` and are filterable.

Public API:
- `Check::valid($rules, $data)` validates data and returns `true` or `false`.
- `Check::method($name, $definition = null)` registers validation methods.
- `Check::errors()` returns the last error map.

Events and filters:
- Uses `Events` and calls `Check::triggerOnce('init')` before validation.
- Error messages can be customized via `Filter::with("core.check.error.<name>", ...)`.

Example:
```php
$rules = [
  'email' => 'required|email',
  'age' => 'min:18',
];
if (!Check::valid($rules, $_POST)) {
  $errors = Check::errors();
}
```

The Check module allow you to validate data in a easy way.

### Validate data
---

The `valid` method check passed keys with the defined methods, in cascade.

> The methods are a ordered priority list separated by `|`. You can pass comma separated `,` parameters to a method with the `methodname:param1,param2,"param string 3"` syntax.

```php
if (!Check::valid([
  'username' => 'required',
  'email'    => 'required | email',
  'age'      => 'required | numeric | in_range:18,90',
  'phone'    => 'numeric',
], $data_to_validate)){
   echo "Errors: " . print_r(Check::errors(),true);
} else {
   echo "OK!";
}
```

### Define a validation method
---

You can define a validation method via the `Check::method($name, $definition)` method.

```php
Check::method('limit_to', [
  'validate' => function($value,$max) {
     return strlen($value) <= $max;
  },
  'message' => "Too many characters, max count is {{arg_1}}.",
]);
```

A `core.check.error.<METHOD_NAME>` Filter is automatically created for the error message.

```php
Filter::add('core.check.error.limit_to',function($message){
  return "Il campo può essere lungo al massimo {{arg_1}} caratteri!";
});
```

You can pass multiple methods in a single call via a `name => definition` associative array.

```php
Check::method([
  'max' => [
    'validate' => function($value, $limit) {
       return $value <= $limit;
    },
    'message' => "Must be less than {{arg_1}}.",
  ],
  'min' => [
    'validate' => function($value, $limit) {
       return $value >= $limit;
    },
    'message' => "Must be greater than {{arg_1}}.",
  ]
]);
```
Methods are initialized on-demand, so it's preferable to define them in the `core.check.init` event.

```php
Event::on('core.check.init',function(){
  Check::method('limit_to', [
    'validate' => function($value,$max) {
       return strlen($value) <= $max;
    },
    'message' => "Too many characters, max count is {{arg_1}}.",
  ]);
});
```

Validation methods can have parameters passed to them, you can define them after the first one which is always the full value.

```php
Check::method('in_range', function($value,$min,$max){
  return (($value>=$min)&&($value<=$max)) ? true : "This value must be in [$min,$max] range.";
});
```

### Built-in methods
---

| Method | Parameters | Description |
|--------|------------|-------------|
`required` | | The value is required _(int(0) is accepted)_
`alphanumeric` | | The value must contains only alphanumeric characters _(RegEx: \w)_
`numeric` | | The value must be a number
`email` | | The value must be a valid email
`url` | | The value must be a valid URL
`max` | `limit` | The value must be less than `limit`
`min` | `limit` | The value must be greater than `limit`
`words` | `limit` | There must be less or equal than `limit` words.
`length` | `limit` | There must be equal than `limit` characters.
`min_length` | `limit` | There must be greater or equal than `limit` characters.
`max_length` | `limit` | There must be less or equal than `limit` characters.
`range` | `min` , `max` | The value is must be between or equal to [ `min` , `max` ] range
`true` | | The value must be true (check PHP manual for trueness evaluation)
`false` | | The value must be false (check PHP manual for trueness evaluation)
`same_as` | `field_name` | The value must be the same as the `field_name` value
`in_array` | `[value, value, value ....]` | The value must be one of the defined in passed array (use JSON syntax)

Example:

```php
Check::valid([
  'username'    => 'required',
  'password'    => 'required',
  'password_v'  => 'required | same_as:"password"',
  'level'       => 'required | in_array:["GUEST","USER","ADMIN"]',
], Request::data()) || echo "Errors: " . print_r(Check::errors(),true);
```

The [Check](./Check.md) module allow you to validate data in a easy way.

### Validate data
---

The `valid` method check passed keys with the defined methods, in cascade.

> The methods are a ordered priority list separated by `|`. You can pass comma separated `,` parameters to a method with the `methodname:param1,param2,"param string 3"` syntax.

```php
if (!Check::valid([
  'username' => 'required',
  'email'    => 'required | email',
  'age'      => 'required | numeric | in_range:18,90',
  'phone'    => 'numeric',
], $data_to_validate)){
   echo "Errors: " . print_r(Check::errors(),true);
} else {
   echo "OK!";
}
```

### Define a validation method
---

You can define a validation method via the `Check::method($name, $definition)` method.

```php
Check::method('limit_to', [
  'validate' => function($value,$max) {
     return strlen($value) <= $max;
  },
  'message' => "Too many characters, max count is {{arg_1}}.",
]);
```

A `core.check.error.<METHOD_NAME>` Filter is automatically created for the error message.

```php
Filter::add('core.check.error.limit_to',function($message){
  return "Il campo può essere lungo al massimo {{arg_1}} caratteri!";
});
```

You can pass multiple methods in a single call via a `name => definition` associative array.

```php
Check::method([
  'max' => [
    'validate' => function($value, $limit) {
       return $value <= $limit;
    },
    'message' => "Must be less than {{arg_1}}.",
  ],
  'min' => [
    'validate' => function($value, $limit) {
       return $value >= $limit;
    },
    'message' => "Must be greater than {{arg_1}}.",
  ]
]);
```
Methods are initialized on-demand, so it's preferable to define them in the `core.check.init` event.

```php
Event::on('core.check.init',function(){
  Check::method('limit_to', [
    'validate' => function($value,$max) {
       return strlen($value) <= $max;
    },
    'message' => "Too many characters, max count is {{arg_1}}.",
  ]);
});
```

Validation methods can have parameters passed to them, you can define them after the first one which is always the full value.

```php
Check::method('in_range', function($value,$min,$max){
  return (($value>=$min)&&($value<=$max)) ? true : "This value must be in [$min,$max] range.";
});
```

### Built-in methods
---

| Method | Parameters | Description |
|--------|------------|-------------|
`required` | | The value is required _(int(0) is accepted)_
`alphanumeric` | | The value must contains only alphanumeric characters _(RegEx: \w)_
`numeric` | | The value must be a number
`email` | | The value must be a valid email
`url` | | The value must be a valid URL
`max` | `limit` | The value must be less than `limit`
`min` | `limit` | The value must be greater than `limit`
`words` | `limit` | There must be less or equal than `limit` words.
`length` | `limit` | There must be equal than `limit` characters.
`min_length` | `limit` | There must be greater or equal than `limit` characters.
`max_length` | `limit` | There must be less or equal than `limit` characters.
`range` | `min` , `max` | The value is must be between or equal to [ `min` , `max` ] range
`true` | | The value must be true (check PHP manual for trueness evaluation)
`false` | | The value must be false (check PHP manual for trueness evaluation)
`same_as` | `field_name` | The value must be the same as the `field_name` value
`in_array` | `[value, value, value ....]` | The value must be one of the defined in passed array (use JSON syntax)

Example:

```php
Check::valid([
  'username'    => 'required',
  'password'    => 'required',
  'password_v'  => 'required | same_as:"password"',
  'level'       => 'required | in_array:["GUEST","USER","ADMIN"]',
], Request::data()) || echo "Errors: " . print_r(Check::errors(),true);
```



