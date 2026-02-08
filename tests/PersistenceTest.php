<?php

use PHPUnit\Framework\TestCase;

class Widget {
  use Persistence;
}

class PersistenceTest extends TestCase {
  public function testPersistenceOptionsDefaults(): void {
    $options = Widget::persistenceOptions();
    $this->assertSame('widgets', $options['table']);
    $this->assertSame('id', $options['key']);
  }
}
