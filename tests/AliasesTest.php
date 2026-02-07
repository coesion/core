<?php

use PHPUnit\Framework\TestCase;

class AliasesTest extends TestCase {
  public function testCoreAliasesRegister(): void {
    $this->assertFalse(class_exists('Core\\Cache', false));

    Core\Aliases::register();

    $this->assertTrue(class_exists('Core\\Cache'));
    $this->assertInstanceOf(Cache::class, new Core\Cache());

    $this->assertFalse(class_exists('Core\\Nope', false));
  }
}
