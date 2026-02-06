<?php

use PHPUnit\Framework\TestCase;
use Email\Driver as EmailDriver;
use Email\Envelope;
use Email\Native as EmailNative;
use Email\Proxy as EmailProxy;
use Email\Smtp as EmailSmtp;
use Email\Ses as EmailSes;

class EmailDriversTest extends TestCase {
  public function testEnvelopeBuildsHeadersAndBody(): void {
    $env = new Envelope();
    $env->from('Sender <sender@example.com>');
    $env->to('Recipient <recipient@example.com>');
    $env->subject('Test Subject');
    $env->message('Hello');

    $head = $env->head();
    $body = $env->body();

    $this->assertStringContainsString('Subject: Test Subject', $head);
    $this->assertStringContainsString('To: Recipient <recipient@example.com>', $head);
    $this->assertStringContainsString('Hello', $body);
  }

  public function testProxyDriverTriggersEvent(): void {
    $env = new Envelope();
    $env->to('user@example.com');

    \Event::on('core.email.proxy.send', function() {
      return true;
    });

    $driver = new EmailProxy();
    $driver->onInit([]);
    $results = $driver->onSend($env);

    $this->assertSame([true], $results);
  }

  public function testSmtpDriverInitSetsDefaults(): void {
    $driver = new EmailSmtp();
    $driver->onInit(['host' => 'smtp.example.com', 'username' => 'user', 'password' => 'pass']);

    $getProp = function(string $prop){
      return $this->$prop;
    };
    $getProp = $getProp->bindTo($driver, $driver);

    $this->assertSame('smtp.example.com', $getProp('host'));
    $this->assertTrue($getProp('secure'));
    $this->assertSame(465, $getProp('port'));
  }

  public function testSesDriverRequiresCredentials(): void {
    $driver = new EmailSes();
    $this->expectException(Exception::class);
    $driver->onInit([]);
  }

  public function testNativeDriverImplementsInterface(): void {
    $driver = new EmailNative();
    $this->assertInstanceOf(EmailDriver::class, $driver);
    $driver->onInit([]);
  }
}
