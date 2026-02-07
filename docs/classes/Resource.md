# Resource

Overview:
`Resource` is the base class for API-exposed entities. Override `expose()` to control output.

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
