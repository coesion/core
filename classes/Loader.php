<?php

/**
 * Loader
 *
 * Easy class autoloading.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Loader {
    protected static $paths = [];

    /**
     * Adds a path to class autoloader
     * @param string $path The path root to add to class autoloader
     * @param string $name An optional name for path section
     */
    public static function addPath($path,$name=null){
        static::$paths[$path] = $name;
    }

    /**
     * Register core autoloader
     * @return bool Returns false if autoloader failed inclusion
     */
    public static function register(){
        if (ini_get('unserialize_callback_func') !== false) {
            ini_set('unserialize_callback_func', 'spl_autoload_call');
        }
        spl_autoload_register(callback: function($class){
            $normalized = strtr($class, '\\', '/');
            $candidates = [$normalized . '.php'];
            if (strpos($normalized, '_') !== false) {
                $candidates[] = strtr($normalized, '_', '/') . '.php';
            }
            foreach (static::$paths as $path => $v) {
                $base = rtrim($path, '/');
                foreach ($candidates as $candidate) {
                    $file = $base . '/' . $candidate;
                    if (is_file($file)) return include($file);
                }
            }
            return false;
        });
    }

}

// Automatically register core classes.
Loader::addPath(__DIR__);
Loader::register();
