# Installation

Overview:
Install the Core package with Composer and include the autoloader. The framework classes are ready to use after autoloading.

Requirements:
- PHP 8.5+
- Composer

Install:
```bash
composer require coesion/core
```

Bootstrap example:
```php
<?php
require __DIR__ . '/vendor/autoload.php';

Route::on('/', function () {
  return 'Hello from Core!';
});

Route::dispatch();
Response::send();
```

Notes:
- `Loader` registers `classes/` automatically for core classes.
- You can extend the autoloader with `Loader::addPath()` if you add your own class tree.
- Published artifact package (`coesion/core`) autoloads `core.php` through Composer `autoload.files`, so `vendor/autoload.php` is enough.

Performance:
- Autoload optimization: `composer dump-autoload -o`
- Build single-file artifact: `php tools/build-core.php`
- OPcache preload (web/FPM): set `opcache.enable=1`, `opcache.preload=/path/to/dist/core.php`, and `opcache.preload_user=www-data` (or your PHP-FPM user). Optional for partial control: `opcache.file_cache=/path/to/opcache`.

Single-file deploy (optional):
- Build: `php tools/build-core.php`
- Include: `require __DIR__ . '/dist/core.php';`
- Note: `dist/core.php` is generated from framework classes and optimized for preload/distribution.

FrankenPHP:
- Classic mode: works like standard PHP-FPM; no special changes required.
- Worker mode: use `tools/frankenphp-worker.php` and ensure `Response::reset()` is called per request.
- Static binary guide: see `FrankenPHP.md`.
