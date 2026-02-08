<?php

/**
 * Auth
 *
 * Lightweight authentication helpers with session and bearer support.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Auth {
  use Module, Events;

  protected static $resolver = null,
                   $tokenResolver = null,
                   $userResolved = false,
                   $user = null,
                   $source = null,
                   $identity = null;

  public static function boot(){
    Route::extend([
      'auth' => function (array $options = []) {
        $this->before(function () use ($options) {
          if (!Auth::check()) {
            $status = $options['status'] ?? Options::get('core.auth.unauthorized.status', 401);
            $body = $options['body'] ?? Options::get('core.auth.unauthorized.body', 'Unauthorized');
            Response::error($status, $body);
            if (!empty($body)) Response::add($body);
            return false;
          }
        });
      },
      'guest' => function () {
        $this->before(function () {
          if (Auth::check()) {
            Response::error(403, 'Forbidden');
            Response::add('Forbidden');
            return false;
          }
        });
      },
      'can' => function (string $ability, ...$args) {
        $this->before(function () use ($ability, $args) {
          if (!Gate::authorize($ability, ...$args)) {
            Response::error(403, 'Forbidden');
            Response::add('Forbidden');
            return false;
          }
        });
      },
      'csrf' => function (array $options = []) {
        $this->before(function () use ($options) {
          if (!CSRF::shouldVerify()) return;
          if (!CSRF::verify($options)) {
            Response::error(419, 'CSRF token mismatch');
            Response::add('CSRF token mismatch');
            return false;
          }
        });
      },
      'rateLimit' => function (int $limit, int $window = 60, ?string $key = null, array $options = []) {
        $this->before(function () use ($limit, $window, $key, $options) {
          if (!Options::get('core.security.rate_limit.enabled', true)) return;
          $result = RateLimiter::check(
            $key ?: RateLimiter::defaultKey(),
            $limit,
            $window
          );
          RateLimiter::applyHeaders($limit, $result['remaining'], $result['reset']);
          if (!$result['allowed']) {
            $retry = max(0, $result['reset'] - time());
            Response::header('Retry-After', (string)$retry);
            Response::error(429, $options['message'] ?? 'Too Many Requests');
            Response::add($options['body'] ?? 'Too Many Requests');
            return false;
          }
        });
      },
      'secureHeaders' => function (array $overrides = []) {
        $this->before(function () use ($overrides) {
          SecurityHeaders::apply($overrides);
        });
      },
    ]);
  }

  public static function resolver(callable $resolver){
    static::$resolver = $resolver;
  }

  public static function tokenResolver(callable $resolver){
    static::$tokenResolver = $resolver;
  }

  public static function user(){
    if (!static::$userResolved) static::resolve();
    return static::$user;
  }

  public static function check(){
    return (bool)static::user();
  }

  public static function id(){
    $user = static::user();
    if (is_object($user)) {
      foreach (['id','ID','user_id','uid'] as $key) {
        if (isset($user->$key)) return $user->$key;
      }
      return $user;
    }
    if (is_array($user)) {
      foreach (['id','ID','user_id','uid'] as $key) {
        if (isset($user[$key])) return $user[$key];
      }
      return $user;
    }
    return $user;
  }

  public static function source(){
    if (!static::$userResolved) static::resolve();
    return static::$source;
  }

  public static function identity(){
    if (!static::$userResolved) static::resolve();
    return static::$identity;
  }

  public static function login($identity){
    if (!Options::get('core.auth.session.enabled', true)) return false;
    Session::start();
    $key = Options::get('core.auth.session.key', 'auth.user');
    Session::set($key, $identity);
    if (Options::get('core.auth.session.regenerate', true)) {
      @session_regenerate_id(true);
    }
    static::flush();
    return true;
  }

  public static function logout(){
    if (!Options::get('core.auth.session.enabled', true)) return false;
    Session::start();
    $key = Options::get('core.auth.session.key', 'auth.user');
    Session::delete($key);
    if (Options::get('core.auth.session.regenerate', true)) {
      @session_regenerate_id(true);
    }
    static::flush();
    return true;
  }

  public static function flush(){
    static::$userResolved = false;
    static::$user = null;
    static::$source = null;
    static::$identity = null;
  }

  protected static function resolve(){
    static::$userResolved = true;
    static::$user = null;
    static::$source = null;
    static::$identity = null;

    $context = [
      'request' => [
        'ip' => Request::IP(),
        'method' => Request::method(),
        'uri' => Request::URI(),
      ],
    ];

    if (Options::get('core.auth.session.enabled', true)) {
      $key = Options::get('core.auth.session.key', 'auth.user');
      if (Session::exists($key)) {
        $identity = Session::get($key);
        static::$identity = $identity;
        static::$source = 'session';
        static::$user = static::resolveUser($identity, 'session', $context);
        return;
      }
    }

    if (Options::get('core.auth.bearer.enabled', true)) {
      $headerName = Options::get('core.auth.bearer.header', 'Authorization');
      $header = Request::header($headerName);
      $schemes = Options::get('core.auth.bearer.schemes', ['Bearer']);
      if ($header) {
        foreach ((array)$schemes as $scheme) {
          $prefix = $scheme . ' ';
          if (stripos($header, $prefix) === 0) {
            $token = trim(substr($header, strlen($prefix)));
            if ($token === '') break;
            $payload = null;
            $isJwt = substr_count($token, '.') === 2;
            if ($isJwt) {
              try {
                $secret = Options::get('core.auth.jwt.secret', null);
                $verify = $secret !== null && $secret !== '';
                $payload = Token::decode($token, $secret, $verify);
                if ($payload && isset($payload->exp)) {
                  if (time() >= (int)$payload->exp) {
                    $payload = null;
                  }
                } elseif (Options::get('core.auth.jwt.require_exp', false)) {
                  $payload = null;
                }
              } catch (Throwable $e) {
                $payload = null;
              }
            }
            $identity = $payload ?: $token;
            $context['token'] = $token;
            $context['payload'] = $payload;
            static::$identity = $identity;
            static::$source = 'bearer';
            $user = null;
            if (static::$tokenResolver) {
              $user = call_user_func(static::$tokenResolver, $token, $payload);
            }
            static::$user = $user !== null ? $user : static::resolveUser($identity, 'bearer', $context);
            return;
          }
        }
      }
    }
  }

  protected static function resolveUser($identity, $source, array $context){
    if (static::$resolver) {
      return call_user_func(static::$resolver, $identity, $source, $context);
    }
    return $identity;
  }
}

