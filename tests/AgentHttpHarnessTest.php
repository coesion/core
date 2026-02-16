<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/support/AgentHttpHarness.php';

class AgentHttpHarnessTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Route::reset();
        Response::reset();
    }

    protected function tearDown(): void {
        Route::reset();
        Response::reset();
        parent::tearDown();
    }

    public function testDispatchReturnsNormalizedEnvelope(): void {
        Route::get('/harness-ok', function () {
            return ['ok' => true];
        });

        $res = AgentHttpHarness::dispatch('GET', '/harness-ok');
        $this->assertSame(200, $res['status']);
        $this->assertArrayHasKey('Content-Type', $res['headers']);
        $this->assertStringContainsString('{"ok":true}', $res['body']);
    }
}
