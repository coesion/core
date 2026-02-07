<?php

/**
 * SecurityHeaders
 *
 * Apply secure HTTP response headers.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class SecurityHeaders {
  use Module;

  public static function apply(array $overrides = []){
    if (!Options::get('core.security.headers.enabled', true)) return;
    $defaults = Options::get('core.security.headers.defaults', [
      'X-Frame-Options' => 'SAMEORIGIN',
      'X-Content-Type-Options' => 'nosniff',
      'Referrer-Policy' => 'strict-origin-when-cross-origin',
      'Permissions-Policy' => 'interest-cohort=()',
      'Cross-Origin-Resource-Policy' => 'same-site',
    ]);

    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
      if (!isset($defaults['Strict-Transport-Security'])) {
        $defaults['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
      }
    }

    $headers = array_merge($defaults, $overrides);
    $existing = Response::headers();
    foreach ($headers as $name => $value) {
      if (isset($existing[$name]) && !array_key_exists($name, $overrides)) continue;
      Response::header($name, $value);
    }
  }
}

