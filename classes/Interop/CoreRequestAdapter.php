<?php

/**
 * CoreRequestAdapter
 *
 * Request adapter for PSR-like interop semantics.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Interop;

class CoreRequestAdapter implements HttpRequestLike {
    protected $method;
    protected $uri;
    protected $headers;
    protected $body;

    public function __construct($method, $uri, $headers = [], $body = '') {
        $this->method = strtoupper((string) $method);
        $this->uri = (string) $uri;
        $this->headers = (array) $headers;
        $this->body = $body;
    }

    public function method() {
        return $this->method;
    }

    public function uri() {
        return $this->uri;
    }

    public function headers() {
        return $this->headers;
    }

    public function body() {
        return $this->body;
    }
}
