# Relation (trait)


Overview:
`Relation` defines relationships between `Model` classes and adds lazy-loading accessors.

Key behavior:
- Relations are defined via `hasOne` and `hasMany`.
- Accessing `$model->relationName` triggers a query.

Public API:
- `::hasOne($modelName, $extra = [])` defines a one-to-one relation.
- `::hasMany($modelName, $extra = [])` defines a one-to-many relation.

Example:
```php
class Post extends Model { public $id; }
class Comment extends Model { public $post_id; }
Post::hasMany('Comment.post_id');
```



