<?php

/**
 * FileSystemNative
 *
 * Native Filesystem
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace FileSystem;

class Native implements Adapter {

  protected $root;

  public function __construct(array $options = []) {
      $this->root = empty($options['root'])?'/':(rtrim($options['root'],'/').'/');
  }

  public function exists($path){
      return file_exists($this->realPath($path));
  }

  public function read($path){
      return $this->exists($path) ? file_get_contents($this->realPath($path)) : false;
  }

  public function write($path, $data){
      $r_path = $this->realPath($path);
      if ( ! is_dir($r_dir = dirname($r_path)) ) @mkdir($r_dir,0775,true);
      return file_put_contents($r_path, $data);
  }

  public function append($path, $data){
      return file_put_contents($this->realPath($path), $data, FILE_APPEND);
  }

  public function move($old, $new){
      // Atomic
      if($this->exists($old)){
          return $this->write($new,$this->read($old)) && $this->delete($old);
      } else return false;
  }

  public function delete($path){
      return $this->exists($path) ? unlink($this->realPath($path)) : false;
  }

  public function search($pattern, $recursive=true){
      $results    = [];
      $root_len   = strlen($this->root);
      $rx_pattern = '('.strtr($pattern,['.'=>'\.','*'=>'.*','?'=>'.']).')Ai';

      $stack = [$this->root];
      while (!empty($stack)) {
          $dir = array_pop($stack);
          $items = @scandir($dir);
          if ($items === false) {
              continue;
          }

          foreach ($items as $item) {
              if ($item === '.' || $item === '..') {
                  continue;
              }

              $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $item;
              if (is_dir($path) && $recursive) {
                  $stack[] = $path;
              }

              if (preg_match($rx_pattern, $path)) {
                  $results[] = trim(substr($path, $root_len),'/');
              }
          }
      }

      return $results;

  }

  protected function realPath($path){
      return $this->root . $path;
  }

}
