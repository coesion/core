<?php

/**
 * Cache\Adapter
 *
 * Cache drivers common interface.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Cache;

interface Adapter  {
  public function get($key);
  public function set($key,$value,$expire=0);
  public function delete($key);
  public function exists($key);
  public function flush();

  public function inc($key,$value=1);
  public function dec($key,$value=1);

  public static function valid();
}