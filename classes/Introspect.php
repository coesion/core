<?php

/**
 * Introspect
 *
 * Runtime introspection API for framework capabilities and class metadata.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Introspect {
    use Module;

    /**
     * List all autoloaded Core classes.
     *
     * @return array Array of class name strings
     */
    public static function classes() {
        $classes = [];
        foreach (get_declared_classes() as $class) {
            $ref = new \ReflectionClass($class);
            $file = $ref->getFileName();
            if ($file && static::isCoreFile($file)) {
                $classes[] = $class;
            }
        }
        foreach (get_declared_traits() as $trait) {
            $ref = new \ReflectionClass($trait);
            $file = $ref->getFileName();
            if ($file && static::isCoreFile($file)) {
                $classes[] = $trait;
            }
        }
        foreach (get_declared_interfaces() as $iface) {
            $ref = new \ReflectionClass($iface);
            $file = $ref->getFileName();
            if ($file && static::isCoreFile($file)) {
                $classes[] = $iface;
            }
        }
        sort($classes);
        return $classes;
    }

    /**
     * List public methods of a class, including Module-injected ones.
     *
     * @param string $class The class name
     * @return array Array of method name strings
     */
    public static function methods($class) {
        if (!class_exists($class) && !trait_exists($class) && !interface_exists($class)) return [];

        $ref = new \ReflectionClass($class);
        $methods = [];
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[] = $method->getName();
        }

        // Add Module-extended methods
        $extended = static::extensions($class);
        $methods = array_unique(array_merge($methods, $extended));
        sort($methods);
        return $methods;
    }

    /**
     * List only the dynamically extended methods of a class (via Module trait).
     *
     * @param string $class The class name
     * @return array Array of method name strings
     */
    public static function extensions($class) {
        if (!class_exists($class)) return [];

        $ref = new \ReflectionClass($class);
        $traits = static::allTraits($ref);

        if (!in_array('Module', $traits, true)) return [];

        try {
            $prop = $ref->getProperty('__PROTOTYPE__');
            $prop->setAccessible(true);
            $proto = $prop->getValue();
            return is_array($proto) ? array_keys($proto) : [];
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * List all registered routes with patterns, methods, and tags.
     *
     * @return array Array of route descriptor arrays
     */
    public static function routes() {
        $result = [];
        if (!class_exists('Route') || !isset(Route::$routes)) return $result;

        foreach ((array)Route::$routes as $group => $list) {
            foreach ((array)$list as $route) {
                if (!is_a($route, 'Route')) continue;
                $ref = new \ReflectionObject($route);
                $data = [];
                foreach (['URLPattern', 'methods', 'tag', 'dynamic'] as $prop) {
                    if ($ref->hasProperty($prop)) {
                        $p = $ref->getProperty($prop);
                        $p->setAccessible(true);
                        $data[$prop] = $p->getValue($route);
                    }
                }
                $result[] = [
                    'pattern' => $data['URLPattern'] ?? '',
                    'methods' => array_keys($data['methods'] ?? []),
                    'tag'     => $data['tag'] ?? '',
                    'dynamic' => $data['dynamic'] ?? false,
                ];
            }
        }
        return $result;
    }

    /**
     * Detect available framework capabilities and extensions.
     *
     * @return array Associative map of capability flags and framework metadata
     */
    public static function capabilities() {
        return [
            'redis'     => extension_loaded('redis'),
            'sodium'    => extension_loaded('sodium') || function_exists('sodium_crypto_secretbox'),
            'curl'      => extension_loaded('curl'),
            'pdo'       => extension_loaded('pdo'),
            'sqlite'    => extension_loaded('pdo_sqlite'),
            'mysql'     => extension_loaded('pdo_mysql'),
            'mbstring'  => extension_loaded('mbstring'),
            'openssl'   => extension_loaded('openssl'),
            'gd'        => extension_loaded('gd'),
            'zip'       => extension_loaded('zip'),
            'json'      => function_exists('json_encode'),
            'session'   => function_exists('session_start'),
            'core'      => [
                'zero_runtime_dependencies' => static::runtimeDependencyCount() === 0,
                'runtime_dependency_count'  => static::runtimeDependencyCount(),
                'introspection_available'   => true,
                'route' => [
                    'loop_mode'       => (bool) Options::get('core.route.loop_mode', false),
                    'loop_dispatcher' => (string) Options::get('core.route.loop_dispatcher', 'fast'),
                    'debug'           => (bool) Options::get('core.route.debug', false),
                ],
                'auth' => [
                    'booted' => static::hasRouteExtension('auth'),
                ],
                'cache' => [
                    'driver_loaded' => static::cacheDriverName() !== '',
                    'driver'        => static::cacheDriverName(),
                ],
                'schedule' => [
                    'registered_jobs' => static::scheduleJobCount(),
                ],
            ],
        ];
    }

    /**
     * Check if a file belongs to the Core classes directory.
     *
     * @param string $file
     * @return bool
     */
    protected static function isCoreFile($file) {
        $file = strtr($file, '\\', '/');
        $classesDir = strtr(dirname(__DIR__), '\\', '/') . '/classes/';
        if (strpos($file, $classesDir) === 0) return true;

        $distCore = strtr(dirname(__DIR__), '\\', '/') . '/dist/core.php';
        return $file === $distCore;
    }

    /**
     * Collect all trait names used by a ReflectionClass, including nested traits.
     *
     * @param \ReflectionClass $ref
     * @return array
     */
    protected static function allTraits(\ReflectionClass $ref) {
        $traits = [];
        foreach ($ref->getTraitNames() as $name) {
            $traits[] = $name;
        }
        $parent = $ref->getParentClass();
        if ($parent) {
            $traits = array_merge($traits, static::allTraits($parent));
        }
        return array_unique($traits);
    }

    /**
     * Count composer runtime dependencies excluding PHP and extension constraints.
     *
     * @return int
     */
    protected static function runtimeDependencyCount() {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'composer.json';
        $json = @file_get_contents($path);
        if (!$json) return 0;

        $composer = json_decode($json, true);
        if (!is_array($composer)) return 0;

        $requires = isset($composer['require']) && is_array($composer['require'])
            ? $composer['require']
            : [];

        $count = 0;
        foreach (array_keys($requires) as $package) {
            $name = strtolower((string) $package);
            if ($name === 'php') continue;
            if (strpos($name, 'ext-') === 0) continue;
            $count++;
        }
        return $count;
    }

    /**
     * Check if Route has a specific dynamic extension.
     *
     * @param string $method
     * @return bool
     */
    protected static function hasRouteExtension($method) {
        return in_array($method, static::extensions('Route'), true);
    }

    /**
     * Return the active cache driver short name, if available.
     *
     * @return string
     */
    protected static function cacheDriverName() {
        if (!class_exists('Cache')) return '';

        try {
            $ref = new \ReflectionClass('Cache');
            if (!$ref->hasProperty('driver')) return '';

            $prop = $ref->getProperty('driver');
            $prop->setAccessible(true);
            $driver = $prop->getValue();

            if (!is_object($driver)) return '';

            $class = get_class($driver);
            $parts = explode('\\', $class);
            return strtolower(end($parts));
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Return number of currently registered scheduled jobs.
     *
     * @return int
     */
    protected static function scheduleJobCount() {
        if (!class_exists('Schedule')) return 0;
        return count(Schedule::all());
    }
}
