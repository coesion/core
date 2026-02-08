<?php

/**
 * Cache\Files
 *
 * Core\Cache Files Driver.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Cache;

class Files implements Adapter {
    protected $options;

    public static function valid(){
        return true;
    }

    public function __construct($options=[]){
        $this->options = (object) array_merge([
            'cache_dir' => sys_get_temp_dir().'/core_file_cache',
        ], $options);
        $this->options->cache_dir = rtrim($this->options->cache_dir,'/');
        if (is_dir($this->options->cache_dir) && !is_writable($this->options->cache_dir)) {
            $this->options->cache_dir = sys_get_temp_dir().'/core_file_cache_'.getmypid();
        }
        if(false===is_dir($this->options->cache_dir)) mkdir($this->options->cache_dir,0777,true);
        $this->options->cache_dir .= '/';
    }

    public function get($key){
        $cache_file_name = $this->options->cache_dir.$key.'.cache.php';
        if(is_file($cache_file_name) && $data = @unserialize(file_get_contents($cache_file_name))){
            if($data[0] && (time() > $data[0])) {
                unlink($cache_file_name);
                return null;
            }
            return $data[1];
        } else {
            return null;
        }
    }

    public function set($key,$value,$expire=0){
        $cache_file_name = $this->options->cache_dir.$key.'.cache.php';
        file_put_contents($cache_file_name,serialize([$expire?time()+$expire:0,$value]));
    }

    public function delete($key){
        $cache_file_name = $this->options->cache_dir.$key.'.cache.php';
      if(is_file($cache_file_name)) unlink($cache_file_name);
    }

    public function exists($key){
        $cache_file_name = $this->options->cache_dir.$key.'.cache.php';
        if(false === is_file($cache_file_name)) return false;
        $raw = file_get_contents($cache_file_name);
        if ($raw === false) return false;
        $data = @unserialize($raw);
        if (!is_array($data) || count($data) < 2) {
            @unlink($cache_file_name);
            return false;
        }
        $expire = $data[0];
        if($expire && $expire < time()){
            unlink($cache_file_name);
            return false;
        }
        return true;
    }

    public function flush(){
        exec('rm -f ' . $this->options->cache_dir . '*.cache.php');
    }

    public function inc($key,$value=1){
        if(null === ($current = $this->get($key))) $current = $value; else $current += $value;
        $this->set($key,$current);
    }

    public function dec($key,$value=1){
        $this->inc($key,-abs($value));
    }
}
