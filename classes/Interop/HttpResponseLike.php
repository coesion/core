<?php

/**
 * HttpResponseLike
 *
 * PSR-like response contract for interop adapters.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Interop;

interface HttpResponseLike {
    public function statusCode();
    public function headers();
    public function body();
}
