<?php

/**
 * Gate
 *
 * Simple authorization gate.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Gate {
  use Module;

  protected static $abilities = [];

  public static function define($ability, callable $callback){
    static::$abilities[$ability] = $callback;
  }

  public static function allows($ability, ...$args){
    if (empty(static::$abilities[$ability])) return false;
    $user = Auth::user();
    return (bool)call_user_func(static::$abilities[$ability], $user, ...$args);
  }

  public static function authorize($ability, ...$args){
    return static::allows($ability, ...$args);
  }
}

