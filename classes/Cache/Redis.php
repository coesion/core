<?php

/**
 * Cache\Redis
 *
 * Core\Cache Redis Driver.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Cache;

class Redis implements Adapter {
    protected $connection;
    protected $prefix;

    /**
     * Check if the Redis extension is available.
     *
     * @return bool
     */
    public static function valid() {
        return extension_loaded('redis');
    }

    /**
     * Create a new Redis cache adapter.
     *
     * @param array $options Connection options: host, port, password, prefix, database
     */
    public function __construct($options = []) {
        $options = (object) array_merge([
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'password' => null,
            'prefix'   => 'core:',
            'database' => 0,
            'timeout'  => 2.0,
        ], $options);

        $this->prefix = $options->prefix;
        $this->connection = new \Redis();
        $this->connection->connect($options->host, (int) $options->port, $options->timeout);

        if ($options->password) {
            $this->connection->auth($options->password);
        }

        if ($options->database) {
            $this->connection->select((int) $options->database);
        }
    }

    /**
     * Get a value from cache.
     *
     * @param string $key
     * @return mixed|null
     */
    public function get($key) {
        $value = $this->connection->get($this->prefix . $key);
        if ($value === false) return null;
        $data = @unserialize($value);
        return $data === false && $value !== serialize(false) ? null : $data;
    }

    /**
     * Set a value in cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int $expire TTL in seconds, 0 for no expiration
     */
    public function set($key, $value, $expire = 0) {
        $prefixed = $this->prefix . $key;
        $this->connection->set($prefixed, serialize($value));
        if ($expire > 0) {
            $this->connection->expire($prefixed, $expire);
        }
    }

    /**
     * Delete a value from cache.
     *
     * @param string $key
     */
    public function delete($key) {
        $this->connection->del($this->prefix . $key);
    }

    /**
     * Check if a key exists in cache.
     *
     * @param string $key
     * @return bool
     */
    public function exists($key) {
        return (bool) $this->connection->exists($this->prefix . $key);
    }

    /**
     * Flush all keys with the current prefix.
     */
    public function flush() {
        if ($this->prefix) {
            $keys = $this->connection->keys($this->prefix . '*');
            if ($keys) {
                $this->connection->del(...$keys);
            }
        } else {
            $this->connection->flushDB();
        }
    }

    /**
     * Increment a numeric value.
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function inc($key, $value = 1) {
        $prefixed = $this->prefix . $key;
        if (!$this->connection->exists($prefixed)) {
            $this->connection->set($prefixed, serialize($value));
            return $value;
        }
        $current = $this->get($key);
        $current = (is_numeric($current) ? $current : 0) + $value;
        $this->connection->set($prefixed, serialize($current));
        return $current;
    }

    /**
     * Decrement a numeric value.
     *
     * @param string $key
     * @param int $value
     */
    public function dec($key, $value = 1) {
        $this->inc($key, -abs($value));
    }
}
