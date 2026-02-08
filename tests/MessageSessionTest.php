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

  public function testMessageReadOnlyExplicit(): void {
    Message::set('notice', 'alert');
    $ro = new MessageReadOnly();
    $this->assertSame('alert', $ro->notice);
    $this->assertSame('', Message::get('notice'));
    $this->assertTrue(isset($ro->any_key));
  }

  public function testSessionReadOnly(): void {
    Session::set('core_readonly_key', 'value');
    $ro = new SessionReadOnly();
    $this->assertSame('value', $ro->get('core_readonly_key'));
    $this->assertSame('value', $ro->core_readonly_key);
    $this->assertTrue($ro->exists('core_readonly_key'));
    $this->assertTrue(isset($ro->core_readonly_key));
    $this->assertSame(Session::name(), $ro->name());
  }
}
