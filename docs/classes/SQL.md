# SQL

Overview:
`SQL` is a PDO-based database wrapper with connection registry and helper methods. It defaults to an in-memory SQLite connection.

Use `SQL` for connection management and common query helpers while keeping direct PDO access available through connection wrappers.

Security note:
- Emulated prepares are disabled by default (real prepares when supported).

Public API (static):
- `SQL::register($name, $dsn, $username = null, $password = null, $options = [])`
- `SQL::connect($dsn, $username = null, $password = null, $options = [])`
- `SQL::defaultTo($name)`
- `SQL::close($name = null)`
- `SQL::hasConnection($name = 'default')`
- `SQL::using($name)`
- `SQL::selectFrom($table, $columns = ['*'])`

`SQLConnection` methods:
- `prepare($query, $pdo_params = [])`
- `exec($query, $params = [], $pdo_params = [])`
- `value($query, $params = [], $column = 0)`
- `column($query, $params = [], $column = 0)`
- `reduce($query, $params = [], $looper = null, $initial = null)`
- `each($query, $params = [], ?callable $looper = null)`
- `single($query, $params = [], ?callable $handler = null)`
- `run($script)` executes a SQL file in `database.sql.path`.
- `all($query, $params = [], ?callable $looper = null)`
- `delete($table, $pks = null, $pk = 'id', $inclusive = true)`
- `insert($table, $data, $pk = 'id')`
- `updateWhere($table, $data, $where, $pk = 'id')`
- `update($table, $data, $pk = 'id', $extra_where = '')`
- `insertOrUpdate($table, $data = [], $pk = 'id', $extra_where = '')`
- `selectFrom($table, $columns = ['*'])`
- `whereEq($filters = [])`
- `orderBy($order = [])`
- `limit($limit, $offset = 0)`
- `toSQL()`
- `get()`

Example:
```php
SQL::connect('mysql:host=localhost;dbname=test', 'user', 'pass');
SQL::exec('CREATE TABLE items (id INT)');
```

Fluent helper example:
```php
$rows = SQL::selectFrom('users', ['id', 'email'])
  ->whereEq(['state' => 'active'])
  ->orderBy(['id' => 'desc'])
  ->limit(20)
  ->get();
```

The SQL module expose a shorthand for common database methods extending the PDO layer.

### Bind to database
---

You can bind the SQL module to a database with a DSN (Data Source Name) string via the `connect` method.
Connection is lazy-loaded at the first database access.

```php
SQL::connect('mysql:host=localhost;dbname=test','root','password');
```

> **Note:** `SQL::connect` comes already connected to an in-memory ephemeral database (SQLite3)

The event `core.sql.connect` si fired upon database connection.

```php
Event::on('core.sql.connect',function($sql){
  $sql->exec('SET NAMES "UTF8"');
});
```

You can register several data sources via the `register` method.

```php
SQL::register('production','mysql:host=database.mysite.com;dbname=production','www','******');
$localDB = SQL::register('local','mysql:host=localhost;dbname=development','root','');
```

You can now use the returned resource for executing SQL methods or access the wanted datasource via the `using` accessor.

```php
$localDB->insert('users',[
  'email' => 'test@other.com',
  'password' => 'kek',
]);

echo SQL::using('local')->value("SELECT password FROM users WHERE email=?",['test@other.com']);
```

```
kek
```

Normally the `SQL::*` methods are binded to the default connection that is registered on the `default` datasource.  
If you want to change it you can with the `defaultTo` method :

```php
// Setting the default datasource, this is the same as we used `SQL::register('default',...`
SQL::connect('mysql:host=database.mysite.com;dbname=production','www','******');

// Setting the `local` datasource
SQL::register('local','mysql:host=localhost;dbname=development','root','');

// Now we are using the `default` datasource as the default one
$users_a = SQL::each("SELECT * FROM users");

SQL::defaultTo("local");

// Now we are using the `local` datasource as the default one
$users_a = SQL::each("SELECT * FROM users");
```

### Execute a SQL statement
---

You can execute a SQL statement with the `exec` method. The query will be prepared and you can pass optional binding parameters as last function argument.

```php
SQL::exec('TRUNCATE TABLE `users`');

SQL::exec('DELETE FROM `users` WHERE `age` < 16');
```

### Retrieve a single value
---

The `value` method executes the query, with the optional parameters and returns the first column of the first row of the results.

```php
$total_users = SQL::value('SELECT COUNT(1) FROM `users`');

$user_is_registered = !!SQL::value('SELECT 1 FROM `users` WHERE username = :usr_name',[
  'usr_name' => $username
]);
```

### Retrieve a single row
---

The `single` method executes the query, with the optional parameters and runs the passed callback with the current row object.

```php
SQL::single('SELECT username, points FROM `rankings` LIMIT 1',function($rank){
  echo 'The Winner is : ',$rank->username,' with ',$rank->points,' points!';
});

$rank = SQL::single('SELECT username, points FROM `rankings` LIMIT 1',function($rank){
  $rank->username = strtoupper($rank->username);
  return $rank;
});
```
```

```

### Retrieve an entire column
---

`SQL::column($query, $params=[], $column_idx=0)`

The `column` method executes the query, with the optional parameters and a third parameter which is the numeric 0-based index of the column or its label and returns a filtered array of values.

```php
$emails = SQL::column('SELECT name, email FROM `users`', [], 1);

// or

$emails = SQL::column('SELECT name, email FROM `users`', [], 'email');
```

```
alpha@beta.com
frank@castle.com
```

### Retrieve rows
---

The `each` method executes the query, with the optional parameters and runs the passed callback with the current row object for every row of the results.

```php
SQL::each('SELECT * FROM `users`',function($user){
  echo '<li><a href="mailto:', $user->email ,'">', $user->name ,'</a></li>';
});
```

### Reduce rows
---

The `reduce` method works like array_reduce function with the query performed.

```php
$users = SQL::reduce('SELECT id,group FROM `users`',function($results, $row){
  $results[$row->group][] = $row->id;
  return $results;
}, []);
```

### Retrieve all results
---

The `all` method is used to retrieve all results in a single call.

```php
echo json_encode( SQL::all('SELECT `name` , `email` FROM `users`') );
```

### Insert a new row
---

The `insert` method is used to insert into a defined table a new row, passed as an associative array.

```php
$inserted_item_id = SQL::insert('users',[
  'name'     => 'Stannis Baratheon',
  'password' => 'im_the_one_true_king',
]);
```

### Update a single row
---

The `update` method is used to change a single row data, passed as an associative array.

```php
SQL::update('users',[
  'id'       => 321,
  'name'     => 'King Stannis Baratheon',
]);
```

You can also override the name of the primary key column as the third function parameter, default is `id`

```php
SQL::update('users',[
  'email'    => 'stannis@baratheon.com',
  'name'     => 'King Stannis Baratheon',
],'email');
```

### Delete a single row
---

The `delete` method is used to remove a single row data.

```php
SQL::delete( 'users', [ 321, 432 ] );
```

You can also override the name of the primary key column as the third function parameter, default is `id`

```php
SQL::delete( 'users', [ 'mario@rossi.it', 'sara@rossi.it' ], 'email' );
```

### Debug queries
---

You can bind a function to the `core.sql.query` event for listening every executed query. 

```php
Event::on('core.sql.query',function($query,$params,$statement){
  echo "SQL Query  : $query \n";
  echo "Parameters : ", print_r($params,true), "\n";
  echo "Success    : ", ($statement?'Yes':'No'), "\n";
});
```
