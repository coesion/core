<?php

/**
 * WebSocket
 *
 * WebSocket messaging facade with pluggable drivers.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class WebSocket {
    use Module, Events;

    protected static $driver = null;

    /**
     * Load a WebSocket driver with FCFS strategy.
     *
     * @param mixed $driver Driver name string, array of names, or map of name => options
     * @return bool True if a driver was loaded
     */
    public static function using($driver) {
        foreach ((array) $driver as $key => $value) {
            if (is_numeric($key)) {
                $drv = $value;
                $conf = [];
            } else {
                $drv = $key;
                $conf = $value;
            }
            $class = 'WebSocket\\' . ucfirst(strtolower($drv));
            if (class_exists($class) && $class::valid()) {
                static::$driver = new $class($conf);
                return true;
            }
        }
        return false;
    }

    /**
     * Send a message to a specific channel.
     *
     * @param string $channel
     * @param mixed $data
     * @return bool
     */
    public static function send($channel, $data) {
        if (!static::$driver) return false;
        $result = static::$driver->send($channel, $data);
        static::trigger('send', $channel, $data, $result);
        return $result;
    }

    /**
     * Broadcast a message to a channel (all subscribers).
     *
     * @param string $channel
     * @param mixed $data
     * @return bool
     */
    public static function broadcast($channel, $data) {
        if (!static::$driver) return false;
        $result = static::$driver->broadcast($channel, $data);
        static::trigger('broadcast', $channel, $data, $result);
        return $result;
    }

    /**
     * Subscribe a callback to channel events.
     *
     * @param string $channel
     * @param callable $callback
     * @return void
     */
    public static function subscribe($channel, callable $callback) {
        static::on("channel:{$channel}", $callback);
    }

    /**
     * Check if a driver is loaded.
     *
     * @return bool
     */
    public static function ready() {
        return static::$driver !== null;
    }
}
