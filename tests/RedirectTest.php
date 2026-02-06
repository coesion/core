<?php

use PHPUnit\Framework\TestCase;

class RedirectTest extends TestCase {
  public function testRedirectMethodsExist(): void {
    $this->assertTrue(is_callable([Redirect::class, 'to']));
    $this->assertTrue(is_callable([Redirect::class, 'back']));
    $this->assertTrue(is_callable([Redirect::class, 'viaJavaScript']));
  }
}
