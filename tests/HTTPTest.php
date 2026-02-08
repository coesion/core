<?php

use PHPUnit\Framework\TestCase;

class HTTPTest extends TestCase {
  public function testHeaderManagementAndUserAgent(): void {
    HTTP::addHeader('X-Test', '1');
    $this->assertSame('1', HTTP::headers('X-Test'));

    HTTP::removeHeader('X-Test');
    $this->assertSame('', HTTP::headers('X-Test'));

    HTTP::userAgent('CoreTestUA');
    $this->assertSame('CoreTestUA', HTTP::userAgent());

    HTTP::proxy('127.0.0.1:8888');
    $this->assertSame('127.0.0.1:8888', HTTP::proxy());
  }

  public function testLastResponseHeaderParsing(): void {
    $setHeader = function(string $value){
      self::$last_response_header = $value;
    };
    $setHeader = $setHeader->bindTo(null, HTTP::class);
    $setHeader("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nX-Test: abc\r\n");

    $headers = HTTP::lastResponseHeader();
    $this->assertSame('text/plain', $headers['Content-Type'][0]);
    $this->assertSame('abc', $headers['X-Test'][0]);
  }

  public function testHttpResponseStringCast(): void {
    $res = new HTTP_Response('body', 200, ['X' => '1']);
    $this->assertSame('body', (string)$res);
  }

  public function testHttpRequestStringCast(): void {
    $req = new HTTP_Request(
      'post',
      'example.com/path?x=1',
      ['Content-Type' => 'application/json'],
      ['a' => 1]
    );

    $raw = (string)$req;
    $this->assertStringStartsWith("POST /path?x=1 HTTP/1.1\r\n", $raw);
    $this->assertStringContainsString("Host: example.com\r\n", $raw);
    $this->assertStringContainsString("\r\n\r\n{\"a\":1}", $raw);
  }
}
