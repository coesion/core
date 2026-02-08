<?php

/**
 * Service Module
 *
 * This module permits the user to register and retrieve a service manager
 * instance, one (singleton) or multiple times
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Service {
    use Module;
    private static $services = [];

    public static function register($serviceName, $serviceFactory){
      static::$services[$serviceName] = function(...$args) use ($serviceName, $serviceFactory) {
        return static::$services[$serviceName] = $serviceFactory(...$args);
      };
    }

    public static function registerFactory($serviceName, $serviceFactory){
        static::$services[$serviceName] = function(...$args) use ($serviceName, $serviceFactory) {
            return $serviceFactory(...$args);
        };
    }

    public static function __callStatic($serviceName, $serviceParameters){
      $servs = static::$services;
    	return empty($servs[$serviceName])
                   ? null
                   : (is_callable($servs[$serviceName])
                       ? $servs[$serviceName](...$serviceParameters)
                       : $servs[$serviceName]
                   );
    }


}
