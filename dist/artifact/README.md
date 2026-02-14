# coesion/core

Artifact-only distribution for Coesion Core.

## Install

```bash
composer require coesion/core
```

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

Route::on('/', function () {
  return 'Core loaded from artifact package.';
});

Route::dispatch();
Response::send();
```

## Notes

- This package is generated from `core-dev` source.
- Composer autoloads `core.php` via `autoload.files`.
- `core.php` includes a `COESION_CORE_LOADED` guard to prevent duplicate parsing.
