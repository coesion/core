<?php

$root = dirname(__DIR__);
$distDir = $root . DIRECTORY_SEPARATOR . 'dist';
$pharPath = $distDir . DIRECTORY_SEPARATOR . 'core.phar';
$classesDir = $root . DIRECTORY_SEPARATOR . 'classes';

if (ini_get('phar.readonly')) {
  fwrite(STDERR, "phar.readonly is enabled. Run with: php -d phar.readonly=0 tools/build-phar.php\n");
  exit(1);
}

if (!is_dir($distDir) && !mkdir($distDir, 0775, true) && !is_dir($distDir)) {
  fwrite(STDERR, "Failed to create dist directory: $distDir\n");
  exit(1);
}

if (file_exists($pharPath)) {
  unlink($pharPath);
}

$phar = new Phar($pharPath, 0, 'core.phar');
$phar->setSignatureAlgorithm(Phar::SHA256);
$phar->startBuffering();

$files = glob($classesDir . DIRECTORY_SEPARATOR . '*.php');
if ($files === false) {
  fwrite(STDERR, "Failed to read classes directory: $classesDir\n");
  exit(1);
}

sort($files, SORT_STRING);
foreach ($files as $file) {
  $local = 'classes/' . basename($file);
  $phar->addFile($file, $local);
}

$stub = <<<'STUB'
<?php
Phar::mapPhar('core.phar');
require 'phar://core.phar/classes/Loader.php';

if (PHP_SAPI === 'cli') {
  echo "Core PHAR loaded.\n";
  echo "This PHAR contains core classes only.\n";
}

__HALT_COMPILER();
STUB;

$phar->setStub($stub);
$phar->stopBuffering();

fwrite(STDOUT, "Built $pharPath\n");
