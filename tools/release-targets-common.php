<?php

/**
 * ReleaseTarget helpers
 *
 * @package core
 */

function artifactTargetsPath(){
  return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'release-targets.json';
}

function artifactReadJsonFile($path){
  if (!is_file($path)) {
    throw new RuntimeException("Missing file: $path");
  }

  $decoded = json_decode((string)file_get_contents($path), true);
  if (!is_array($decoded)) {
    throw new RuntimeException("Invalid JSON in $path");
  }

  return $decoded;
}

function artifactGetPath(array $data, $path){
  $current = $data;
  foreach (explode('.', $path) as $part) {
    if (!is_array($current) || !array_key_exists($part, $current)) {
      throw new RuntimeException("Missing required manifest key '$path'");
    }
    $current = $current[$part];
  }

  return $current;
}

function artifactValidateSemver($value, $name){
  if (!is_string($value) || !preg_match('/^\d+\.\d+\.\d+$/', $value)) {
    throw new RuntimeException("Invalid $name '$value'. Expected X.Y.Z");
  }
}

function artifactValidateRepo($value, $key){
  if (!is_string($value) || !preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $value)) {
    throw new RuntimeException("Invalid $key '$value'. Expected owner/name");
  }
}

function artifactValidatePhpPackageName($value, $key){
  if (!is_string($value) || !preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/', $value)) {
    throw new RuntimeException("Invalid $key '$value'. Expected vendor/name lowercase");
  }
}

function artifactValidateJsPackageName($value, $key){
  if (!is_string($value) || !preg_match('/^@[a-z0-9][a-z0-9_-]*\/[a-z0-9][a-z0-9._-]*$/i', $value)) {
    throw new RuntimeException("Invalid $key '$value'. Expected @scope/name");
  }
}

function artifactValidateManifest(array $manifest){
  $required = [
    'defaults.tag_pattern',
    'defaults.publish_mode',
    'artifacts.php.repo',
    'artifacts.php.branch',
    'artifacts.php.package_name',
    'artifacts.php.homepage',
    'artifacts.js.repo',
    'artifacts.js.branch',
    'artifacts.js.package_name',
    'artifacts.js.homepage',
    'artifacts.js.registry',
  ];

  foreach ($required as $key) {
    artifactGetPath($manifest, $key);
  }

  $tagPattern = artifactGetPath($manifest, 'defaults.tag_pattern');
  if (!is_string($tagPattern) || trim($tagPattern) === '') {
    throw new RuntimeException('defaults.tag_pattern must be a non-empty string');
  }

  $publishMode = artifactGetPath($manifest, 'defaults.publish_mode');
  if ($publishMode !== 'auto_on_tag') {
    throw new RuntimeException("Unsupported defaults.publish_mode '$publishMode'. Expected auto_on_tag");
  }

  artifactValidateRepo(artifactGetPath($manifest, 'artifacts.php.repo'), 'artifacts.php.repo');
  artifactValidatePhpPackageName(artifactGetPath($manifest, 'artifacts.php.package_name'), 'artifacts.php.package_name');

  artifactValidateRepo(artifactGetPath($manifest, 'artifacts.js.repo'), 'artifacts.js.repo');
  artifactValidateJsPackageName(artifactGetPath($manifest, 'artifacts.js.package_name'), 'artifacts.js.package_name');

  $jsRegistry = artifactGetPath($manifest, 'artifacts.js.registry');
  if (!is_string($jsRegistry) || !preg_match('/^https:\/\//', $jsRegistry)) {
    throw new RuntimeException("Invalid artifacts.js.registry '$jsRegistry'. Expected https:// URL");
  }

  $phpBranch = artifactGetPath($manifest, 'artifacts.php.branch');
  $jsBranch = artifactGetPath($manifest, 'artifacts.js.branch');
  if (!is_string($phpBranch) || trim($phpBranch) === '') {
    throw new RuntimeException('artifacts.php.branch must be a non-empty string');
  }
  if (!is_string($jsBranch) || trim($jsBranch) === '') {
    throw new RuntimeException('artifacts.js.branch must be a non-empty string');
  }
}

function artifactReadManifest(){
  $manifest = artifactReadJsonFile(artifactTargetsPath());
  artifactValidateManifest($manifest);
  return $manifest;
}

function artifactReadJsVersion(){
  $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'VERSION';
  if (!is_file($path)) {
    throw new RuntimeException("Missing JS version file at $path");
  }

  $version = trim((string)file_get_contents($path));
  artifactValidateSemver($version, 'js/VERSION');
  return $version;
}

function artifactReadRootVersion(){
  $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'VERSION';
  if (!is_file($path)) {
    throw new RuntimeException("Missing VERSION file at $path");
  }

  $version = trim((string)file_get_contents($path));
  artifactValidateSemver($version, 'VERSION');
  return $version;
}

function artifactReadJsPackage(){
  $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'package.json';
  return artifactReadJsonFile($path);
}

function artifactWriteJsonFile($path, array $payload){
  $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if ($json === false) {
    throw new RuntimeException("Failed encoding JSON for $path");
  }
  if (file_put_contents($path, $json . "\n") === false) {
    throw new RuntimeException("Failed writing $path");
  }
}

function artifactExportEnvFor($artifact, array $manifest){
  $defaults = artifactGetPath($manifest, 'defaults');
  $php = artifactGetPath($manifest, 'artifacts.php');
  $js = artifactGetPath($manifest, 'artifacts.js');

  $env = [
    'TAG_PATTERN' => $defaults['tag_pattern'],
    'PUBLISH_MODE' => $defaults['publish_mode'],
  ];

  if ($artifact === 'php' || $artifact === 'all') {
    $env['PHP_ARTIFACT_REPO'] = $php['repo'];
    $env['PHP_ARTIFACT_BRANCH'] = $php['branch'];
    $env['PHP_PACKAGE_NAME'] = $php['package_name'];
    $env['PHP_PACKAGE_HOMEPAGE'] = $php['homepage'];
  }

  if ($artifact === 'js' || $artifact === 'all') {
    $env['JS_ARTIFACT_REPO'] = $js['repo'];
    $env['JS_ARTIFACT_BRANCH'] = $js['branch'];
    $env['JS_PACKAGE_NAME'] = $js['package_name'];
    $env['JS_PACKAGE_HOMEPAGE'] = $js['homepage'];
    $env['JS_PACKAGE_REGISTRY'] = $js['registry'];
  }

  return $env;
}
