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
}
