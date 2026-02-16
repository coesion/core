<?php

/**
 * HttpRequestLike
 *
 * PSR-like request contract for interop adapters.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Interop;

interface HttpRequestLike {
    public function method();
    public function uri();
    public function headers();
    public function body();
}
