<?php

/**
 * Schema
 *
 * Database schema introspection for models and tables.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Schema {
    use Module;

    protected static $cache = [];

    /**
     * Describe columns for a model class or table name.
     *
     * @param string $modelOrTable A Model class name or a raw table name
     * @return array Array of column descriptors with name, type, nullable, default, key
     */
    public static function describe($modelOrTable) {
        $table = static::resolveTable($modelOrTable);
        if (isset(static::$cache[$table])) return static::$cache[$table];

        $driver = static::detectDriver();

        if ($driver === 'sqlite') {
            $columns = static::describeSQLite($table);
        } else {
            $columns = static::describeMySQL($table);
        }

        return static::$cache[$table] = $columns;
    }

    /**
     * List all tables in the current database.
     *
     * @return array Array of table name strings
     */
    public static function tables() {
        $driver = static::detectDriver();

        if ($driver === 'sqlite') {
            return SQL::column("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        }

        return SQL::column("SHOW TABLES");
    }

    /**
     * Get column names for a model or table.
     *
     * @param string $modelOrTable A Model class name or a raw table name
     * @return array Flat array of column name strings
     */
    public static function columns($modelOrTable) {
        return array_column(static::describe($modelOrTable), 'name');
    }

    /**
     * Check if a table exists in the current database.
     *
     * @param string $table The table name
     * @return bool
     */
    public static function hasTable($table) {
        return in_array($table, static::tables(), true);
    }

    /**
     * Clear the internal schema cache.
     *
     * @return void
     */
    public static function flush() {
        static::$cache = [];
    }

    /**
     * Resolve a model class name or table string to a table name.
     *
     * @param string $modelOrTable
     * @return string
     */
    protected static function resolveTable($modelOrTable) {
        if (class_exists($modelOrTable) && is_subclass_of($modelOrTable, 'Model')) {
            return $modelOrTable::persistenceOptions('table');
        }
        return $modelOrTable;
    }

    /**
     * Detect the current PDO driver name.
     *
     * @return string 'sqlite', 'mysql', or the raw driver name
     */
    protected static function detectDriver() {
        try {
            $driver = SQL::value("SELECT 'sqlite' WHERE 1=0");
            // If we get here, connection is valid. Check driver via DSN-based heuristic.
            $pdo = static::getPDO();
            if ($pdo) return $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Exception $e) {}
        return 'sqlite';
    }

    /**
     * Get the underlying PDO instance.
     *
     * @return \PDO|null
     */
    protected static function getPDO() {
        // Access the default connection's PDO
        if (SQL::hasConnection('default')) {
            $conn = SQL::using('default');
            if (method_exists($conn, 'connection')) {
                return $conn->connection();
            }
        }
        return null;
    }

    /**
     * Describe table columns for SQLite.
     *
     * @param string $table
     * @return array
     */
    protected static function describeSQLite($table) {
        $columns = [];
        $rows = SQL::each("PRAGMA table_info(`$table`)");
        if ($rows) {
            foreach ($rows as $row) {
                $row = (object) $row;
                $columns[] = [
                    'name'     => $row->name,
                    'type'     => strtolower($row->type ?: 'text'),
                    'nullable' => !$row->notnull,
                    'default'  => $row->dflt_value,
                    'key'      => $row->pk ? 'PRI' : '',
                ];
            }
        }
        return $columns;
    }

    /**
     * Describe table columns for MySQL.
     *
     * @param string $table
     * @return array
     */
    protected static function describeMySQL($table) {
        $columns = [];
        $rows = SQL::each("SHOW COLUMNS FROM `$table`");
        if ($rows) {
            foreach ($rows as $row) {
                $row = (object) $row;
                $columns[] = [
                    'name'     => $row->Field,
                    'type'     => strtolower($row->Type),
                    'nullable' => $row->Null === 'YES',
                    'default'  => $row->Default,
                    'key'      => $row->Key === 'PRI' ? 'PRI' : ($row->Key ?: ''),
                ];
            }
        }
        return $columns;
    }
}
