<?php

/**
 * CoreResponseAdapter
 *
 * Response adapter for PSR-like interop semantics.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Interop;

class CoreResponseAdapter implements HttpResponseLike {
    protected $status;
    protected $headers;
    protected $body;

    public function __construct($status = 200, $headers = [], $body = '') {
        $this->status = (int) $status;
        $this->headers = (array) $headers;
        $this->body = (string) $body;
    }

    public function statusCode() {
        return $this->status;
    }

    public function headers() {
        return $this->headers;
    }

    public function body() {
        return $this->body;
    }
}
