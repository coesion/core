<?php

use PHPUnit\Framework\TestCase;
use Cache\Adapter as CacheAdapter;
use Cache\Files as CacheFiles;
use Cache\Memory as CacheMemory;

class CacheDriversTest extends TestCase {
  public function testMemoryCacheBasicOperations(): void {
    $cache = new CacheMemory();
    $this->assertInstanceOf(CacheAdapter::class, $cache);

    $cache->set('foo', 3);
    $this->assertTrue($cache->exists('foo'));
    $this->assertSame(3, $cache->get('foo'));

    $cache->inc('foo', 2);
    $this->assertSame(5, $cache->get('foo'));

    $cache->dec('foo', 3);
    $this->assertSame(2, $cache->get('foo'));

    $cache->delete('foo');
    $this->assertFalse($cache->exists('foo'));
  }

  public function testFilesCacheBasicOperations(): void {
    $dir = sys_get_temp_dir() . '/core_cache_test_' . uniqid();
    $cache = new CacheFiles(['cache_dir' => $dir]);
    $this->assertInstanceOf(CacheAdapter::class, $cache);

    $cache->set('alpha', 'beta');
    $this->assertTrue($cache->exists('alpha'));
    $this->assertSame('beta', $cache->get('alpha'));

    $cache->delete('alpha');
    $this->assertFalse($cache->exists('alpha'));

    foreach (glob($dir . '/*.cache.php') as $file) {
      @unlink($file);
    }
    @rmdir($dir);
  }
}
