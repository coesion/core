<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// Application bootstrap goes here.
// Example: require __DIR__ . '/../app/bootstrap.php';

if (!function_exists('frankenphp_request')) {
  fwrite(STDERR, "frankenphp_request() not available. Run with FrankenPHP worker mode.\n");
  exit(1);
}

while (frankenphp_request()) {
  Response::reset();
  Route::dispatch();
  Response::send();
}
