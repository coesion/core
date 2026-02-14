<?php

require_once __DIR__ . '/release-targets-common.php';

function jsArtifactDeletePath($path){
  if (!file_exists($path)) {
    return;
  }

  if (is_file($path) || is_link($path)) {
    @unlink($path);
    return;
  }

  $items = scandir($path);
  if (!is_array($items)) {
    return;
  }

  foreach ($items as $item) {
    if ($item === '.' || $item === '..') {
      continue;
    }
    jsArtifactDeletePath($path . DIRECTORY_SEPARATOR . $item);
  }

  @rmdir($path);
}

function jsArtifactCopyDir($source, $target){
  if (!is_dir($source)) {
    throw new RuntimeException("Missing source directory $source");
  }

  if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
    throw new RuntimeException("Failed to create directory $target");
  }

  $items = scandir($source);
  if (!is_array($items)) {
    throw new RuntimeException("Failed to read directory $source");
  }

  foreach ($items as $item) {
    if ($item === '.' || $item === '..') {
      continue;
    }

    $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
    $targetPath = $target . DIRECTORY_SEPARATOR . $item;

    if (is_dir($sourcePath)) {
      jsArtifactCopyDir($sourcePath, $targetPath);
      continue;
    }

    if (!copy($sourcePath, $targetPath)) {
      throw new RuntimeException("Failed to copy $sourcePath to $targetPath");
    }
  }
}

try {
  $root = dirname(__DIR__);
  $jsRoot = $root . DIRECTORY_SEPARATOR . 'js';
  $distCorePath = $jsRoot . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'core.js';
  $srcPath = $jsRoot . DIRECTORY_SEPARATOR . 'src';
  $artifactDir = $jsRoot . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'artifact';

  $manifest = artifactReadManifest();
  $jsTarget = artifactGetPath($manifest, 'artifacts.js');
  $version = artifactReadJsVersion();

  if (!is_file($distCorePath)) {
    throw new RuntimeException("Missing $distCorePath. Run npm --prefix js run build first.");
  }

  jsArtifactDeletePath($artifactDir);
  if (!is_dir($artifactDir) && !mkdir($artifactDir, 0775, true) && !is_dir($artifactDir)) {
    throw new RuntimeException("Failed to create artifact directory $artifactDir");
  }

  jsArtifactCopyDir($srcPath, $artifactDir . DIRECTORY_SEPARATOR . 'src');

  $coreEntry = "// Generated artifact entry for Core JS\nmodule.exports = require('./src/index');\n";
  if (file_put_contents($artifactDir . DIRECTORY_SEPARATOR . 'core.js', $coreEntry) === false) {
    throw new RuntimeException('Failed writing artifact core.js');
  }

  $indexEntry = "module.exports = require('./core');\n";
  if (file_put_contents($artifactDir . DIRECTORY_SEPARATOR . 'index.js', $indexEntry) === false) {
    throw new RuntimeException('Failed writing artifact index.js');
  }

  $package = [
    'name' => $jsTarget['package_name'],
    'version' => $version,
    'description' => 'Coesion Core JavaScript runtime artifact package.',
    'license' => 'MIT',
    'homepage' => $jsTarget['homepage'],
    'repository' => [
      'type' => 'git',
      'url' => 'https://github.com/' . $jsTarget['repo'] . '.git',
    ],
    'type' => 'commonjs',
    'main' => 'index.js',
    'exports' => [
      '.' => './index.js',
      './bundle' => './core.js',
    ],
    'engines' => [
      'node' => '>=22',
    ],
    'files' => [
      'index.js',
      'core.js',
      'src',
      'README.md',
      'LICENSE.md',
      'CHANGELOG.md',
    ],
  ];

  artifactWriteJsonFile($artifactDir . DIRECTORY_SEPARATOR . 'package.json', $package);

  $readme = <<<MD
# {$jsTarget['package_name']}

Artifact-only distribution for Coesion Core JS.

## Install

```bash
npm install {$jsTarget['package_name']}
```

## Usage

```js
const { Route, Response } = require('{$jsTarget['package_name']}');

Route.get('/', () => 'Core JS loaded from artifact package.');
Response.send();
```

## Notes

- This package is generated from the factory repository.
- Runtime entrypoints are `index.js` and `core.js`.
- Source-only directories like tests and scripts are excluded.
MD;

  if (file_put_contents($artifactDir . DIRECTORY_SEPARATOR . 'README.md', $readme . "\n") === false) {
    throw new RuntimeException('Failed writing artifact README.md');
  }

  $licensePath = $root . DIRECTORY_SEPARATOR . 'LICENSE.md';
  if (is_file($licensePath) && !copy($licensePath, $artifactDir . DIRECTORY_SEPARATOR . 'LICENSE.md')) {
    throw new RuntimeException('Failed to copy LICENSE.md');
  }

  $changelogPath = $root . DIRECTORY_SEPARATOR . 'CHANGELOG.md';
  if (is_file($changelogPath) && !copy($changelogPath, $artifactDir . DIRECTORY_SEPARATOR . 'CHANGELOG.md')) {
    throw new RuntimeException('Failed to copy CHANGELOG.md');
  }

  fwrite(STDOUT, "Built JS artifact repository payload in $artifactDir\n");
} catch (Throwable $e) {
  fwrite(STDERR, $e->getMessage() . "\n");
  exit(1);
}
