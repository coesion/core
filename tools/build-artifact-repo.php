<?php

$root = dirname(__DIR__);
$distDir = $root . DIRECTORY_SEPARATOR . 'dist';
$corePath = $distDir . DIRECTORY_SEPARATOR . 'core.php';
$artifactDir = $distDir . DIRECTORY_SEPARATOR . 'artifact';
$licensePath = $root . DIRECTORY_SEPARATOR . 'LICENSE.md';
$changelogPath = $root . DIRECTORY_SEPARATOR . 'CHANGELOG.md';

if (!is_file($corePath)) {
  fwrite(STDERR, "Missing $corePath. Run php tools/build-core.php first.\n");
  exit(1);
}

if (!is_dir($artifactDir) && !mkdir($artifactDir, 0775, true) && !is_dir($artifactDir)) {
  fwrite(STDERR, "Failed to create artifact directory: $artifactDir\n");
  exit(1);
}

$coreTarget = $artifactDir . DIRECTORY_SEPARATOR . 'core.php';
if (!copy($corePath, $coreTarget)) {
  fwrite(STDERR, "Failed to copy artifact core.php\n");
  exit(1);
}

$composer = [
  'name' => 'coesion/core',
  'type' => 'library',
  'description' => 'Coesion Core runtime artifact package.',
  'keywords' => ['framework', 'core', 'sdk', 'artifact'],
  'homepage' => 'https://github.com/coesion/core',
  'license' => 'MIT',
  'authors' => [
    [
      'name' => 'Stefano Azzolini',
      'email' => 'lastguest@gmail.com',
    ],
  ],
  'require' => [
    'php' => '>=8.5',
  ],
  'autoload' => [
    'files' => ['core.php'],
  ],
];

$composerJson = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($composerJson === false) {
  fwrite(STDERR, "Failed to encode artifact composer.json\n");
  exit(1);
}
$composerJson .= "\n";

if (file_put_contents($artifactDir . DIRECTORY_SEPARATOR . 'composer.json', $composerJson) === false) {
  fwrite(STDERR, "Failed to write artifact composer.json\n");
  exit(1);
}

$readme = <<<MD
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
MD;

if (file_put_contents($artifactDir . DIRECTORY_SEPARATOR . 'README.md', $readme . "\n") === false) {
  fwrite(STDERR, "Failed to write artifact README.md\n");
  exit(1);
}

if (is_file($licensePath) && !copy($licensePath, $artifactDir . DIRECTORY_SEPARATOR . 'LICENSE.md')) {
  fwrite(STDERR, "Failed to copy LICENSE.md\n");
  exit(1);
}

if (is_file($changelogPath) && !copy($changelogPath, $artifactDir . DIRECTORY_SEPARATOR . 'CHANGELOG.md')) {
  fwrite(STDERR, "Failed to copy CHANGELOG.md\n");
  exit(1);
}

fwrite(STDOUT, "Built artifact repository payload in $artifactDir\n");
