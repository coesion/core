# REST

Overview:
`REST` exposes resources with CRUD methods mapped to a RESTful API.

Requirements:
- The API Core Bundle is required for `REST` support.

Expose a resource:
```php
REST::expose('bucket', [
  'create' => function () { echo 'NEW bucket'; },
  'read' => function ($id) { echo "SHOW bucket($id)"; },
  'update' => function ($id) { echo "MODIFY bucket($id)"; },
  'delete' => function ($id) { echo "DELETE bucket($id)"; },
  'list' => function () { echo 'LIST buckets'; },
  'clear' => function () { echo 'CLEAR all buckets'; },
]);
```

Request:
```
GET /bucket/123
```

Response:
```
SHOW bucket(123)
```

Database-backed example:
```php
REST::expose('post', [
  'create' => function () { return SQL::insert('posts', Input::data()); },
  'read' => function ($id) { return SQL::single('select * from posts where id=:id', ['id' => $id]); },
  'update' => function ($id) { return SQL::update('posts', ['id' => $id] + Input::data()); },
  'delete' => function ($id) { return SQL::delete('posts', $id); },
  'list' => function () { return SQL::each('select * from posts'); },
  'clear' => function () { return SQL::delete('posts'); },
]);
```
