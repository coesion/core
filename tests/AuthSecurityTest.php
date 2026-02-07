<?php

use PHPUnit\Framework\TestCase;

class AuthSecurityTest extends TestCase {
  protected $serverBackup = [];

  protected function setUp(): void {
    $this->serverBackup = $_SERVER;
    Auth::flush();
    Options::set('core.auth.session.enabled', true);
    Options::set('core.auth.bearer.enabled', true);
    Options::set('core.auth.session.key', 'auth.user');
    Options::set('core.auth.jwt.secret', null);
    Session::delete('auth.user');
  }

  protected function tearDown(): void {
    $_SERVER = $this->serverBackup;
    Auth::flush();
  }

  public function testAuthSessionResolver(): void {
    Auth::resolver(function ($identity, $source) {
      return ['id' => $identity, 'source' => $source];
    });

    Session::set('auth.user', 7);
    $user = Auth::user();
    $this->assertSame(7, $user['id']);
    $this->assertSame('session', $user['source']);
  }

  public function testAuthBearerJwtResolver(): void {
    $token = Token::encode(['sub' => 5, 'exp' => time() + 60], 'secret');
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    Options::set('core.auth.jwt.secret', 'secret');

    Auth::resolver(function ($identity, $source) {
      if ($source === 'bearer' && isset($identity->sub)) {
        return ['id' => $identity->sub];
      }
      return null;
    });

    $user = Auth::user();
    $this->assertSame(5, $user['id']);
  }

  public function testCsrfTokenVerify(): void {
    $token = Csrf::token();
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
    $this->assertTrue(Csrf::verify());
  }

  public function testGateAllows(): void {
    Gate::define('admin', function ($user) {
      return $user && $user['role'] === 'admin';
    });
    Auth::resolver(function () {
      return ['role' => 'admin'];
    });
    Session::set('auth.user', 1);

    $this->assertTrue(Gate::allows('admin'));
  }

  public function testRateLimiter(): void {
    $key = 'test-' . microtime(true);
    $first = RateLimiter::check($key, 2, 60);
    $second = RateLimiter::check($key, 2, 60);
    $third = RateLimiter::check($key, 2, 60);

    $this->assertTrue($first['allowed']);
    $this->assertTrue($second['allowed']);
    $this->assertFalse($third['allowed']);
  }

  public function testSecurityHeadersApplied(): void {
    Response::reset();
    SecurityHeaders::apply();
    $headers = Response::headers();
    $this->assertArrayHasKey('X-Frame-Options', $headers);
    $this->assertArrayHasKey('X-Content-Type-Options', $headers);
  }
}
