<?php

/**
 * MiddlewareLike
 *
 * PSR-like middleware contract for interop adapters.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Interop;

interface MiddlewareLike {
    public function process(HttpRequestLike $request, RequestHandlerLike $handler);
}
