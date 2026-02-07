<?php

/**
 * Csrf
 *
 * CSRF token helper.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Csrf {
  use Module;

  public static function token(){
    Session::start();
    $key = Options::get('core.csrf.session_key', '_csrf');
    if (!Session::exists($key)) {
      $token = static::generateToken();
      Session::set($key, $token);
    }
    return Session::get($key);
  }

  public static function verify($options = []){
    Session::start();
    $key = Options::get('core.csrf.session_key', '_csrf');
    if (!Session::exists($key)) return false;

    $headerName = $options['header'] ?? Options::get('core.csrf.header', 'X-CSRF-Token');
    $inputName = $options['input'] ?? Options::get('core.csrf.input', '_csrf');

    $token = Request::header($headerName);
    if (!$token) {
      $token = Request::input($inputName);
      if (is_object($token)) $token = null;
    }
    if (!$token) return false;

    $stored = Session::get($key);
    $valid = Password::compare($stored, $token);
    if ($valid && Options::get('core.csrf.rotate', true)) {
      Session::set($key, static::generateToken());
    }
    return $valid;
  }

  public static function rotate(){
    Session::start();
    $key = Options::get('core.csrf.session_key', '_csrf');
    Session::set($key, static::generateToken());
  }

  public static function shouldVerify(){
    $methods = Options::get('core.csrf.methods', ['post','put','patch','delete']);
    return in_array(strtolower(Request::method()), (array)$methods, true);
  }

  protected static function generateToken(){
    if (function_exists('random_bytes')) {
      return bin2hex(random_bytes(32));
    }
    return bin2hex(openssl_random_pseudo_bytes(32));
  }
}

