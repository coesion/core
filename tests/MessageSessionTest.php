<?php

use PHPUnit\Framework\TestCase;

class MessageSessionTest extends TestCase {
  public function testSessionSetGetDelete(): void {
    Session::set('core_test_key', 'value');
    $this->assertSame('value', Session::get('core_test_key'));

    Session::delete('core_test_key');
    $this->assertFalse(Session::exists('core_test_key'));
  }

  public function testMessageSetAndGetClears(): void {
    Message::set('notice', 'hello');
    $this->assertSame('hello', Message::get('notice'));
    $this->assertSame('', Message::get('notice'));
  }

  public function testMessageReadOnlyAccessor(): void {
    Message::set('greeting', 'hi');
    $ro = Message::readOnly();
    $this->assertSame('hi', $ro->greeting);
  }
}
