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
        return call_user_func(['Service', $id]);
    }

    public function has($id) {
        $ref = new \ReflectionClass('Service');
        $prop = $ref->getProperty('services');
        $prop->setAccessible(true);
        $services = (array) $prop->getValue();
        return array_key_exists((string) $id, $services);
    }
}
