<?php

/**
 * RequestHandlerLike
 *
 * PSR-like handler contract for interop adapters.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Interop;

interface RequestHandlerLike {
    public function handle(HttpRequestLike $request);
}
