<?php

/**
 * Migration
 *
 * Deterministic schema migration registry and executor.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Migration {
    use Module;

    protected static $migrations = [];

    /**
     * Register a migration.
     *
     * @param string $id
     * @param callable $up
     * @param callable|null $down
     * @return void
     */
    public static function register($id, $up, $down = null) {
        static::$migrations[(string) $id] = [
            'id' => (string) $id,
            'up' => $up,
            'down' => $down,
        ];
        ksort(static::$migrations);
    }

    /**
     * Clear registered migrations.
     *
     * @return void
     */
    public static function flush() {
        static::$migrations = [];
    }

    /**
     * Return registered migrations.
     *
     * @return array
     */
    public static function all() {
        return static::$migrations;
    }

    /**
     * Return migration execution status.
     *
     * @return array
     */
    public static function status() {
        static::ensureStore();
        $applied = static::appliedIds();
        $registered = array_keys(static::$migrations);
        $pending = array_values(array_diff($registered, $applied));

        return [
            'registered' => $registered,
            'applied' => $applied,
            'pending' => $pending,
        ];
    }

    /**
     * Return migration plan without executing changes.
     *
     * @param string $to
     * @return array
     */
    public static function plan($to = 'latest') {
        $status = static::status();
        $pending = $status['pending'];
        if ($to !== 'latest') {
            $filtered = [];
            foreach ($pending as $id) {
                $filtered[] = $id;
                if ($id === $to) break;
            }
            $pending = $filtered;
        }
        return $pending;
    }

    /**
     * Apply pending migrations up to target.
     *
     * @param string $to
     * @return array
     */
    public static function apply($to = 'latest') {
        static::ensureStore();
        $appliedNow = [];
        foreach (static::plan($to) as $id) {
            $migration = static::$migrations[$id] ?? null;
            if (!$migration || !is_callable($migration['up'])) continue;
            call_user_func($migration['up']);
            SQL::exec('INSERT INTO core_migrations(id, applied_at) VALUES(?, ?)', [$id, gmdate('c')]);
            $appliedNow[] = $id;
        }
        return $appliedNow;
    }

    /**
     * Rollback applied migrations.
     *
     * @param int $steps
     * @return array
     */
    public static function rollback($steps = 1) {
        static::ensureStore();
        $steps = max(0, (int) $steps);
        if ($steps === 0) return [];

        $applied = array_reverse(static::appliedIds());
        $rolled = [];
        foreach ($applied as $id) {
            if (count($rolled) >= $steps) break;
            $migration = static::$migrations[$id] ?? null;
            if ($migration && is_callable($migration['down'])) {
                call_user_func($migration['down']);
            }
            SQL::exec('DELETE FROM core_migrations WHERE id = ?', [$id]);
            $rolled[] = $id;
        }

        return $rolled;
    }

    /**
     * Ensure migration tracking table exists.
     *
     * @return void
     */
    protected static function ensureStore() {
        SQL::exec('CREATE TABLE IF NOT EXISTS core_migrations (id TEXT PRIMARY KEY, applied_at TEXT NOT NULL)');
    }

    /**
     * Return applied migration ids sorted ascending.
     *
     * @return array
     */
    protected static function appliedIds() {
        $rows = SQL::column('SELECT id FROM core_migrations ORDER BY id ASC');
        return array_values(array_map('strval', (array) $rows));
    }
}
