<?php

$distPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'core.php';
if (!is_file($distPath)) {
  throw new RuntimeException("Missing dist/core.php. Run: php tools/build-core.php");
}

$composerLoaders = [];
foreach ((array)spl_autoload_functions() as $loader) {
  if (is_array($loader) && isset($loader[0]) && is_object($loader[0]) && $loader[0] instanceof Composer\Autoload\ClassLoader) {
    $composerLoaders[] = $loader;
    spl_autoload_unregister($loader);
  }
}

require_once $distPath;

if (class_exists('Core\\Aliases', false)) {
  Core\Aliases::register();
}

if (class_exists('Cache', false)) {
  Cache::using(['files', 'memory']);
}

if (class_exists('Email\\Native', false)) {
  Email::using('native');
}

foreach ($composerLoaders as $loader) {
  spl_autoload_register($loader);
}
