<?php

/**
 * AgentHttpHarness
 *
 * Deterministic HTTP dispatch helper for agent workflow tests.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class AgentHttpHarness {

    /**
     * Dispatch a route request and return normalized response data.
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param mixed $body
     * @return array
     */
    public static function dispatch($method, $uri, $headers = [], $body = null) {
        $backupServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = strtoupper((string) $method);
        $_SERVER['REQUEST_URI'] = (string) $uri;

        if ($body !== null) {
            $_POST = is_array($body) ? $body : [];
        }

        Response::reset();
        ob_start();
        Route::dispatch($uri, strtolower((string) $method));
        ob_end_clean();

        $res = [
            'status' => static::readStatic(Response::class, 'status'),
            'headers' => static::normalizeHeaders(static::readStatic(Response::class, 'headers')),
            'body' => Response::body(),
        ];

        $_SERVER = $backupServer;
        return $res;
    }

    /**
     * @param string $class
     * @param string $property
     * @return mixed
     */
    protected static function readStatic($class, $property) {
        $ref = new ReflectionClass($class);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue();
    }

    /**
     * @param array $headers
     * @return array
     */
    protected static function normalizeHeaders($headers) {
        $result = [];
        foreach ((array) $headers as $name => $values) {
            $flat = [];
            foreach ((array) $values as $entry) {
                if (is_array($entry)) {
                    $flat[] = (string) $entry[0];
                } else {
                    $flat[] = (string) $entry;
                }
            }
            $result[(string) $name] = $flat;
        }
        ksort($result);
        return $result;
    }
}
