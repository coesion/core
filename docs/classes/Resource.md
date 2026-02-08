# Resource

Overview:
`Resource` is the base class for API-exposed entities. Override `expose()` to control output.

Use `Resource` to define how model fields are exposed externally, including transformed output for list and detail representations.

Minimal example:

```php
class Category extends Resource {}
```

Custom exposure:

```php
class Article extends Resource {
  public function expose($fields, $mode) {
    return [
      "id"    => $fields->slug,
      "title" => $fields->title,
    ];
  }
}
```

Select exposure mode:

```php
Resource::setExposure("list");
```

See also:
- [API](API.md)
- [Collection](Collection.md)
