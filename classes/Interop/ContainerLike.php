<?php

/**
 * ContainerLike
 *
 * PSR-like container contract for interop adapters.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Interop;

interface ContainerLike {
    public function get($id);
    public function has($id);
}
