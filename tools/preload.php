<?php

$root = dirname(__DIR__);
$classesDir = $root . DIRECTORY_SEPARATOR . 'classes';

$files = glob($classesDir . DIRECTORY_SEPARATOR . '*.php');
if ($files === false) {
  return;
}

sort($files, SORT_STRING);
foreach ($files as $file) {
  require_once $file;
}
