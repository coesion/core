<?php

/**
 * FileSystem\Adapter Interface
 *
 * A Virtual Filesystem
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace FileSystem;

interface Adapter {

  public function exists($path);
  public function read($path);
  public function write($path, $data);
  public function append($path, $data);
  public function delete($path);
  public function move($old_path, $new_path);
  public function search($pattern, $recursive=true);

}
