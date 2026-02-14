<?php

use PHPUnit\Framework\TestCase;
use WebSocket\Adapter as WSAdapter;

class MockWebSocketAdapter implements WSAdapter {
    public $sent = [];
    public $broadcasted = [];

    public static function valid() {
        return true;
    }

    public function send($channel, $data) {
        $this->sent[] = ['channel' => $channel, 'data' => $data];
        return true;
    }

    public function broadcast($channel, $data) {
        $this->broadcasted[] = ['channel' => $channel, 'data' => $data];
        return true;
    }
}

class WebSocketTest extends TestCase {

    public function testAdapterInterface(): void {
        $adapter = new MockWebSocketAdapter();
        $this->assertInstanceOf(WSAdapter::class, $adapter);
        $this->assertTrue(MockWebSocketAdapter::valid());
    }

    public function testSendReturnsFalseWithoutDriver(): void {
        // Reset by creating a fresh state
        $ref = new ReflectionClass('WebSocket');
        $prop = $ref->getProperty('driver');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->assertFalse(WebSocket::send('test', 'data'));
    }

    public function testReadyReturnsFalseWithoutDriver(): void {
        $ref = new ReflectionClass('WebSocket');
        $prop = $ref->getProperty('driver');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->assertFalse(WebSocket::ready());
    }

    public function testSubscribeRegistersCallback(): void {
        $called = false;
        WebSocket::subscribe('test-channel', function() use (&$called) {
            $called = true;
        });
        WebSocket::trigger('channel:test-channel');
        $this->assertTrue($called);
    }

    public function testPusherAdapterValid(): void {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('curl extension not available');
        }
        $this->assertTrue(\WebSocket\Pusher::valid());
    }
}
