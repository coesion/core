# API

Overview:
The API module exposes data via RESTful endpoints and provides base classes for resources.

Implemented classes:
- `API` manages routes setup and endpoints.
- `Resource` represents a resource to expose.

## Expose a resource

Create a resource by extending `Resource`:

```php
class Category extends Resource {}
```

Equivalent to:

```php
class Category extends Resource {
  public function expose($fields, $mode) {
    return $fields;
  }
}
```

Expose an endpoint:

```php
API::resource("/categories", [
  "class"     => "Category",
  "list_mode" => "list",
  "sql"       => [
    "table" => "categories",
  ],
]);
```

`API::resource($path, array $options)` parameters:

| Name | Description | Required | Default |
|------|-------------|----------|---------|
| `class` | Class extending `Resource` for the resource. | YES | `null` |
| `sql.table` | Table to read data from. | NO | `null` |
| `sql.raw` | Custom SQL query. | NO | `null` |
| `sql.primary_key` | Primary key column name. | YES | `id` |

At least one of `sql.raw` or `sql.table` must be provided. `sql.raw` overrides `sql.table`.

Example output:

```json
{
  "data": [
    {
      "id": "lifestyle",
      "name": "Lifestyle",
      "thumbnail": "/media/batband2.jpg"
    },
    {
      "id": "generale",
      "name": "Generale",
      "thumbnail": null
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "count": 8
  }
}
```

Pagination parameters:

| Name | Description |
|------|-------------|
| `page` | 1-based page index. |
| `limit` | Items per page. |

## Custom exposure (marshalling)

Override `expose()` to control output:

```php
class Article extends Resource {
  public function expose($fields, $mode) {
    return [
      "id"        => $fields->slug,
      "title"     => $fields->title,
      "thumbnail" => $fields->thumbnail,
      "content"   => $fields->content,
      "created"   => date("Y-m-d H:i:s", $fields->created),
      "lang"      => $fields->lang,
      "tags"      => explode(',', $fields->tags),
      "seo"       => [
        "title"       => $fields->seo_title,
        "keywords"    => $fields->seo_keywords,
        "description" => $fields->seo_description,
      ],
    ];
  }
}
```

`API::resource()` installs a different exposure mode for list vs detail. In list mode, `$mode` is `list`.

Select a custom exposure mode:

```php
Resource::setExposure("my-custom-mode");
```

Example list exposure:

```php
class Article extends Resource {
  public function expose($fields, $mode) {
    switch ($mode) {
      case "list": return [
        "id"        => $fields->slug,
        "title"     => $fields->title,
        "thumbnail" => $fields->thumbnail,
      ];
      default: return [
        "id"        => $fields->slug,
        "title"     => $fields->title,
        "thumbnail" => $fields->thumbnail,
        "content"   => $fields->content,
        "created"   => date("Y-m-d H:i:s",$fields->created),
        "lang"      => $fields->lang,
        "tags"      => explode(',', $fields->tags),
        "seo"       => [
          "title"       => $fields->seo_title,
          "keywords"    => $fields->seo_keywords,
          "description" => $fields->seo_description,
        ],
      ];
    }
  }
}
```

List output:

```json
// http://api.example/article?limit=3
{
  "data": [
    {
      "id": "social-banking",
      "title": "Social Banking",
      "thumbnail": "alfa12.jpg"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 3,
    "count": 919
  }
}
```

Detail output:

```json
// http://api.example/article/hello-marco
{
  "data": {
    "id": "hello-marco",
    "title": "Hello Marco!",
    "thumbnail": "marco12.png",
    "content": "...",
    "created": "2013-07-19 08:06:33",
    "lang": "IT",
    "tags": [
      "team",
      "bank",
      "hello"
    ],
    "seo": {
      "title": "...",
      "keywords": "...",
      "description": "..."
    }
  }
}
```

## Projection

Select a subset of fields using the `fields` query parameter:

```
?fields=<field1>,<field2>,<field3>,...
```

Example:

```json
// http://api.example/article/hello-marco?fields=title,thumbnail
{
  "data": {
    "id": "hello-marco",
    "title": "Hello Marco!",
    "thumbnail": "marco12.png"
  }
}
```

When using `API::resource()`, the shorthand:

```
/article/hello-marco?fields=title,thumbnail
```

can be written as:

```
/article/hello-marco/title,thumbnail
```

## Custom SQL queries

```php
API::resource("/article", [
  "class"    => "Article",
  "sql"      => [
    "raw"         => "SELECT a.*, c.name AS category_name FROM articles_view a JOIN categories c on c.id = a.id_category",
    "primary_key" => "slug",
  ],
]);
```

Expose joined fields like `category_name`:

```php
class Article extends Resource {
  public function expose($fields, $mode) {
    switch ($mode) {
      case "list": return [
        "id"        => $fields->slug,
        "title"     => $fields->title,
        "thumbnail" => $fields->thumbnail,
        "category"  => $fields->category_name,
      ];
      default: return [
        "id"        => $fields->slug,
        "title"     => $fields->title,
        "thumbnail" => $fields->thumbnail,
        "content"   => $fields->content,
        "created"   => date("Y-m-d H:i:s", $fields->created),
        "lang"      => $fields->lang,
        "category"  => $fields->category_name,
        "tags"      => explode(',', $fields->tags),
        "seo"       => [
          "title"       => $fields->seo_title,
          "keywords"    => $fields->seo_keywords,
          "description" => $fields->seo_description,
        ],
      ];
    }
  }
}
```
