<?php

/**
 * Event
 *
 * Generic event emitter-listener.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Event {
    use Module, Events;

    public static function single($name,callable $listener){
        return static::onSingle($name,$listener);
    }
}
