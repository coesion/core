<?php

/**
 * RateLimiter
 *
 * Fixed-window rate limiting using Cache or in-memory fallback.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class RateLimiter {
  use Module;

  protected static $local = [];

  public static function check(string $key, int $limit, int $window){
    $window = max(1, $window);
    $now = time();
    $entry = static::getEntry($key, $window, $now);

    if ($now > $entry['reset']) {
      $entry = [
        'count' => 0,
        'reset' => $now + $window,
      ];
    }

    $entry['count']++;
    $allowed = $entry['count'] <= $limit;
    static::storeEntry($key, $entry, $window);

    return [
      'allowed' => $allowed,
      'remaining' => max(0, $limit - $entry['count']),
      'reset' => $entry['reset'],
    ];
  }

  public static function applyHeaders(int $limit, int $remaining, int $reset){
    Response::header('X-RateLimit-Limit', (string)$limit);
    Response::header('X-RateLimit-Remaining', (string)$remaining);
    Response::header('X-RateLimit-Reset', (string)$reset);
  }

  public static function defaultKey(){
    $mode = Options::get('core.security.rate_limit.key', 'ip:route');
    $ip = Request::IP();
    $route = Request::URI();
    $method = Request::method();
    switch ($mode) {
      case 'ip':
        return "{$ip}";
      case 'route':
        return "{$method}:{$route}";
      case 'ip:route':
      default:
        return "{$ip}:{$method}:{$route}";
    }
  }

  protected static function getEntry(string $key, int $window, int $now){
    $cacheKey = "rate:" . $key;
    try {
      return Cache::get($cacheKey, function () use ($window, $now) {
        return [
          'count' => 0,
          'reset' => $now + $window,
        ];
      }, $window);
    } catch (Throwable $e) {
      if (!isset(static::$local[$cacheKey])) {
        static::$local[$cacheKey] = [
          'count' => 0,
          'reset' => $now + $window,
        ];
      }
      return static::$local[$cacheKey];
    }
  }

  protected static function storeEntry(string $key, array $entry, int $window){
    $cacheKey = "rate:" . $key;
    try {
      Cache::set($cacheKey, $entry, $window);
    } catch (Throwable $e) {
      static::$local[$cacheKey] = $entry;
    }
  }
}
