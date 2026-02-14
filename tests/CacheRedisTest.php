<?php

use PHPUnit\Framework\TestCase;
use Cache\Redis as CacheRedis;
use Cache\Adapter as CacheAdapter;

class CacheRedisTest extends TestCase {

    protected function setUp(): void {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available');
        }
        parent::setUp();
    }

    public function testValidReturnsTrue(): void {
        $this->assertTrue(CacheRedis::valid());
    }

    public function testImplementsAdapter(): void {
        try {
            $cache = new CacheRedis(['prefix' => 'core_test:']);
            $this->assertInstanceOf(CacheAdapter::class, $cache);
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis server not available: ' . $e->getMessage());
        }
    }

    public function testBasicOperations(): void {
        try {
            $cache = new CacheRedis(['prefix' => 'core_test:']);
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis server not available: ' . $e->getMessage());
        }

        $cache->set('foo', 'bar');
        $this->assertTrue($cache->exists('foo'));
        $this->assertSame('bar', $cache->get('foo'));

        $cache->delete('foo');
        $this->assertFalse($cache->exists('foo'));
        $this->assertNull($cache->get('foo'));
    }

    public function testIncDec(): void {
        try {
            $cache = new CacheRedis(['prefix' => 'core_test:']);
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis server not available: ' . $e->getMessage());
        }

        $cache->set('counter', 10);
        $cache->inc('counter', 5);
        $this->assertSame(15, $cache->get('counter'));

        $cache->dec('counter', 3);
        $this->assertSame(12, $cache->get('counter'));

        $cache->delete('counter');
    }

    public function testFlush(): void {
        try {
            $cache = new CacheRedis(['prefix' => 'core_test:']);
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis server not available: ' . $e->getMessage());
        }

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->flush();
        $this->assertFalse($cache->exists('a'));
        $this->assertFalse($cache->exists('b'));
    }
}
