<?php

/**
 * CoreMiddlewarePipelineAdapter
 *
 * Middleware dispatcher for PSR-like interop semantics.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Interop;

class CoreMiddlewarePipelineAdapter implements RequestHandlerLike {
    protected $stack = [];
    protected $terminal;

    public function __construct($stack = [], $terminal = null) {
        $this->stack = array_values((array) $stack);
        $this->terminal = $terminal ?: function (HttpRequestLike $request) {
            return new CoreResponseAdapter(404, [], 'Not Found');
        };
    }

    public function handle(HttpRequestLike $request) {
        return $this->dispatch(0, $request);
    }

    protected function dispatch($index, HttpRequestLike $request) {
        if (!isset($this->stack[$index])) {
            return call_user_func($this->terminal, $request);
        }

        $current = $this->stack[$index];

        if ($current instanceof MiddlewareLike) {
            $next = new self(array_slice($this->stack, $index + 1), $this->terminal);
            return $current->process($request, $next);
        }

        if (is_callable($current)) {
            $next = new self(array_slice($this->stack, $index + 1), $this->terminal);
            return $current($request, $next);
        }

        return $this->dispatch($index + 1, $request);
    }
}
