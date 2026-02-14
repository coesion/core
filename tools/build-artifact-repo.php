<?php

require_once __DIR__ . '/release-targets-common.php';

$root = dirname(__DIR__);
$distDir = $root . DIRECTORY_SEPARATOR . 'dist';
$corePath = $distDir . DIRECTORY_SEPARATOR . 'core.php';
$artifactDir = $distDir . DIRECTORY_SEPARATOR . 'artifact';
$licensePath = $root . DIRECTORY_SEPARATOR . 'LICENSE.md';
$changelogPath = $root . DIRECTORY_SEPARATOR . 'CHANGELOG.md';

try {
  $manifest = artifactReadManifest();
  $phpArtifact = artifactGetPath($manifest, 'artifacts.php');

  if (!is_file($corePath)) {
    throw new RuntimeException("Missing $corePath. Run php tools/build-core.php first.");
  }

  if (!is_dir($artifactDir) && !mkdir($artifactDir, 0775, true) && !is_dir($artifactDir)) {
    throw new RuntimeException("Failed to create artifact directory: $artifactDir");
  }

  $coreTarget = $artifactDir . DIRECTORY_SEPARATOR . 'core.php';
  if (!copy($corePath, $coreTarget)) {
    throw new RuntimeException('Failed to copy artifact core.php');
  }

  $composer = [
    'name' => $phpArtifact['package_name'],
    'type' => 'library',
    'description' => 'Coesion Core runtime artifact package.',
    'keywords' => ['framework', 'core', 'sdk', 'artifact'],
    'homepage' => $phpArtifact['homepage'],
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

  artifactWriteJsonFile($artifactDir . DIRECTORY_SEPARATOR . 'composer.json', $composer);

  $readme = <<<MD
# {$phpArtifact['package_name']}

Artifact-only distribution for Coesion Core.

## Install

```bash
composer require {$phpArtifact['package_name']}
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
    throw new RuntimeException('Failed to write artifact README.md');
  }

  if (is_file($licensePath) && !copy($licensePath, $artifactDir . DIRECTORY_SEPARATOR . 'LICENSE.md')) {
    throw new RuntimeException('Failed to copy LICENSE.md');
  }

  if (is_file($changelogPath) && !copy($changelogPath, $artifactDir . DIRECTORY_SEPARATOR . 'CHANGELOG.md')) {
    throw new RuntimeException('Failed to copy CHANGELOG.md');
  }

  fwrite(STDOUT, "Built artifact repository payload in $artifactDir\n");
} catch (Throwable $e) {
  fwrite(STDERR, $e->getMessage() . "\n");
  exit(1);
}
