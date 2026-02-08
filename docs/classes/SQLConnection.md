# SQLConnection

Overview:
SQLConnection is the per-connection PDO wrapper used by SQL. It implements query execution, data fetching, and insert/update helpers.

Public API:
- `prepare($query, $pdo_params = [])`
- `exec($query, $params = [], $pdo_params = [])`
- `value($query, $params = [], $column = 0)`
- `column($query, $params = [], $column = 0)`
- `reduce($query, $params = [], $looper = null, $initial = null)`
- `each($query, $params = [], ?callable $looper = null)`
- `single($query, $params = [], ?callable $handler = null)`
- `run($script)`
- `all($query, $params = [], ?callable $looper = null)`
- `delete($table, $pks = null, $pk = 'id', $inclusive = true)`
- `insert($table, $data, $pk = 'id')`
- `updateWhere($table, $data, $where, $pk = 'id')`
- `update($table, $data, $pk = 'id', $extra_where = '')`
- `insertOrUpdate($table, $data = [], $pk = 'id', $extra_where = '')`


