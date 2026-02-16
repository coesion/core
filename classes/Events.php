<?php

/**
 * Events trait
 *
 * Add to a class for a generic, private event emitter-listener.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

trait Events {

    protected static $_listeners = [];

    public static function on($name,callable $listener){
        static::$_listeners[$name][] = $listener;
    }

    public static function onSingle($name,callable $listener){
        static::$_listeners[$name] = [$listener];
    }

    public static function off($name, ?callable $listener = null){
        if($listener === null) {
            unset(static::$_listeners[$name]);
            return;
        }

        if (!isset(static::$_listeners[$name]) || !is_array(static::$_listeners[$name])) {
            return;
        }

        $idx = array_search($listener, static::$_listeners[$name], true);
        if ($idx !== false) {
            unset(static::$_listeners[$name][$idx]);
            static::$_listeners[$name] = array_values(static::$_listeners[$name]);
        }
    }

    public static function alias($source,$alias){
        static::$_listeners[$alias] =& static::$_listeners[$source];
    }

    public static function trigger($name, ...$args){
        if (false === empty(static::$_listeners[$name])){
            $results = [];
            foreach (static::$_listeners[$name] as $listener) {
                $results[] = $listener(...$args);
            }
            return $results;
        };
    }

    public static function triggerOnce($name, ...$args){
        $res = static::trigger($name, ...$args);
        unset(static::$_listeners[$name]);
        return $res;
    }

}
