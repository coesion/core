<?php

/**
 * View\Adapter
 *
 * Core\View\Adapter Interface.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace View;

interface Adapter {
    public function __construct($path=null, $options=[]);
    public function render($template,$data=[]);
    public static function exists($path);
    public static function addGlobal($key,$val);
    public static function addGlobals(array $defs);
}
