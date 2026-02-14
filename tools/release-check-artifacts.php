<?php

require_once __DIR__ . '/release-common.php';
require_once __DIR__ . '/release-targets-common.php';

function releaseCheckReadArgs(array $argv){
  $args = [
    'artifact' => 'all',
    'format' => 'text',
    'artifact_dir' => null,
  ];

  foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--artifact=') === 0) {
      $args['artifact'] = substr($arg, strlen('--artifact='));
      continue;
    }

    if (strpos($arg, '--format=') === 0) {
      $args['format'] = substr($arg, strlen('--format='));
      continue;
    }

    if (strpos($arg, '--artifact-dir=') === 0) {
      $args['artifact_dir'] = substr($arg, strlen('--artifact-dir='));
      continue;
    }

    throw new RuntimeException("Unknown argument: $arg");
  }

  if (!in_array($args['artifact'], ['all', 'php', 'js'], true)) {
    throw new RuntimeException("Invalid --artifact value '{$args['artifact']}'. Use all|php|js");
  }

  if (!in_array($args['format'], ['text', 'json', 'env'], true)) {
    throw new RuntimeException("Invalid --format value '{$args['format']}'. Use text|json|env");
  }

  return $args;
}

function releaseCheckRequireFiles($base, array $required, $label){
  foreach ($required as $file) {
    $path = $base . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path)) {
      throw new RuntimeException("$label artifact missing required file: $file");
    }
  }
}

function releaseCheckPhpArtifactDir($dir, array $manifest){
  releaseCheckRequireFiles($dir, ['core.php', 'composer.json', 'README.md', 'LICENSE.md'], 'PHP');

  $composer = artifactReadJsonFile($dir . DIRECTORY_SEPARATOR . 'composer.json');
  $expectedPackage = artifactGetPath($manifest, 'artifacts.php.package_name');

  if (($composer['name'] ?? null) !== $expectedPackage) {
    throw new RuntimeException("PHP artifact composer.json name mismatch. Expected $expectedPackage");
  }

  $autoloadFiles = $composer['autoload']['files'] ?? [];
  if (!is_array($autoloadFiles) || !in_array('core.php', $autoloadFiles, true)) {
    throw new RuntimeException('PHP artifact composer.json must include autoload.files => ["core.php"]');
  }
}

function releaseCheckJsArtifactDir($dir, array $manifest, $jsVersion){
  releaseCheckRequireFiles($dir, ['package.json', 'README.md', 'LICENSE.md', 'index.js', 'core.js', 'src/index.js'], 'JS');

  $package = artifactReadJsonFile($dir . DIRECTORY_SEPARATOR . 'package.json');
  $expectedPackage = artifactGetPath($manifest, 'artifacts.js.package_name');

  if (($package['name'] ?? null) !== $expectedPackage) {
    throw new RuntimeException("JS artifact package.json name mismatch. Expected $expectedPackage");
  }

  if (($package['version'] ?? null) !== $jsVersion) {
    throw new RuntimeException("JS artifact package.json version mismatch. Expected $jsVersion");
  }

  $main = $package['main'] ?? null;
  if (!is_string($main) || !is_file($dir . DIRECTORY_SEPARATOR . $main)) {
    throw new RuntimeException('JS artifact package.json main entry must exist on disk');
  }

  $exports = $package['exports'] ?? [];
  if (!is_array($exports) || !isset($exports['.']) || !isset($exports['./bundle'])) {
    throw new RuntimeException('JS artifact package.json must include exports for . and ./bundle');
  }

  foreach (['.', './bundle'] as $key) {
    $entry = $exports[$key];
    if (!is_string($entry) || !is_file($dir . DIRECTORY_SEPARATOR . ltrim($entry, './'))) {
      throw new RuntimeException("JS artifact export '$key' does not resolve to a file");
    }
  }

  foreach (['tests', 'scripts'] as $forbidden) {
    if (is_dir($dir . DIRECTORY_SEPARATOR . $forbidden)) {
      throw new RuntimeException("JS artifact must not include '$forbidden' directory");
    }
  }
}

try {
  $args = releaseCheckReadArgs($argv);
  $manifest = artifactReadManifest();
  $rootVersion = artifactReadRootVersion();
  $coreVersion = releaseReadCoreConstVersion();

  if ($rootVersion !== $coreVersion) {
    throw new RuntimeException("Core::VERSION ($coreVersion) does not match VERSION ($rootVersion)");
  }

  $jsVersion = artifactReadJsVersion();
  $jsPackage = artifactReadJsPackage();
  if (($jsPackage['version'] ?? null) !== $jsVersion) {
    throw new RuntimeException("js/package.json version ({$jsPackage['version']}) does not match js/VERSION ($jsVersion)");
  }

  if ($args['artifact_dir'] !== null) {
    $artifactDir = $args['artifact_dir'];
    if (!is_dir($artifactDir)) {
      throw new RuntimeException("Artifact directory not found: $artifactDir");
    }

    if ($args['artifact'] === 'php') {
      releaseCheckPhpArtifactDir($artifactDir, $manifest);
    } elseif ($args['artifact'] === 'js') {
      releaseCheckJsArtifactDir($artifactDir, $manifest, $jsVersion);
    } else {
      releaseCheckPhpArtifactDir($artifactDir . DIRECTORY_SEPARATOR . 'php', $manifest);
      releaseCheckJsArtifactDir($artifactDir . DIRECTORY_SEPARATOR . 'js', $manifest, $jsVersion);
    }
  }

  if ($args['format'] === 'env') {
    foreach (artifactExportEnvFor($args['artifact'], $manifest) as $key => $value) {
      echo $key . '=' . $value . "\n";
    }
    exit(0);
  }

  if ($args['format'] === 'json') {
    $payload = [
      'status' => 'ok',
      'artifact' => $args['artifact'],
      'defaults' => artifactGetPath($manifest, 'defaults'),
      'php' => artifactGetPath($manifest, 'artifacts.php'),
      'js' => artifactGetPath($manifest, 'artifacts.js'),
      'versions' => [
        'php' => $rootVersion,
        'js' => $jsVersion,
      ],
    ];
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
  }

  echo "release-check-artifacts: OK\n";
} catch (Throwable $e) {
  fwrite(STDERR, '[release-check-artifacts] ' . $e->getMessage() . "\n");
  exit(1);
}
