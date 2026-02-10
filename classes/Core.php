<?php

/**
 * Core
 *
 * Runtime diagnostics and metadata.
 */

class Core {
  public const VERSION = '1.1.0';

  public static function version(){
    return self::VERSION;
  }

  public static function diagnostics(){
    $opcache = function_exists('opcache_get_status') ? opcache_get_status(false) : false;
    $opcacheConfig = function_exists('opcache_get_configuration') ? opcache_get_configuration() : false;

    return [
      'version' => self::VERSION,
      'php' => PHP_VERSION,
      'sapi' => PHP_SAPI,
      'extensions' => get_loaded_extensions(),
      'opcache' => $opcache,
      'opcache_config' => $opcacheConfig,
      'preload' => ini_get('opcache.preload') ?: '',
    ];
  }

  public static function log($level, $message, array $context = []){
    Event::trigger('core.log', $level, $message, $context);
  }
}

// Optional namespaced aliases.
\Core\Aliases::register();
