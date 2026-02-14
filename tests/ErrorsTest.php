<?php

use PHPUnit\Framework\TestCase;

class ErrorsTest extends TestCase {
  public function testErrorAliasAndMode(): void {
    $this->assertTrue(class_exists('Error'));

    Errors::mode(Errors::JSON);
    $this->assertSame(Errors::JSON, Errors::mode());

    ob_start();
    Errors::traceException(new Exception('boom'));
    $out = ob_get_clean();

    $this->assertStringContainsString('"error":"boom"', $out);
  }

  public function testStructuredExceptionContract(): void {
    $prev = new RuntimeException('prev');
    $e = new RuntimeException('boom', 99, $prev);
    $payload = Errors::structuredException($e);

    $this->assertArrayHasKey('error', $payload);
    $this->assertArrayHasKey('type', $payload);
    $this->assertArrayHasKey('code', $payload);
    $this->assertArrayHasKey('file', $payload);
    $this->assertArrayHasKey('line', $payload);
    $this->assertArrayHasKey('trace', $payload);
    $this->assertIsArray($payload['trace']);
    $this->assertArrayHasKey('previous', $payload);
  }

  public function testJsonVerboseTraceExceptionOutputsContract(): void {
    Errors::mode(Errors::JSON_VERBOSE);

    ob_start();
    Errors::traceException(new RuntimeException('verbose'));
    $raw = ob_get_clean();

    $payload = json_decode($raw, true);
    $this->assertIsArray($payload);
    $this->assertSame('verbose', $payload['error']);
    $this->assertArrayHasKey('type', $payload);
    $this->assertArrayHasKey('code', $payload);
    $this->assertArrayHasKey('file', $payload);
    $this->assertArrayHasKey('line', $payload);
    $this->assertArrayHasKey('trace', $payload);
    $this->assertIsArray($payload['trace']);
  }
}
