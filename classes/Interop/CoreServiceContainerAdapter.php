<?php

/**
 * CoreServiceContainerAdapter
 *
 * Service container adapter for PSR-like interop semantics.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Interop;

class CoreServiceContainerAdapter implements ContainerLike {
    public function get($id) {
        $method = (string) $id;
        return \Service::$method();
    }

    public function has($id) {
        $ref = new \ReflectionClass('Service');
        $prop = $ref->getProperty('services');
        $services = (array) $prop->getValue();
        return array_key_exists((string) $id, $services);
    }
}
