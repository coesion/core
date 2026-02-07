<?php

require __DIR__ . '/../classes/Loader.php';

Options::set('core.route.debug', true);

function usage() {
  echo "Usage: php tools/route_debug.php [tree|stats]\n";
  exit(1);
}

$cmd = $argv[1] ?? null;
if ($cmd === null) {
  usage();
}

switch ($cmd) {
  case 'tree':
    Route::compile();
    echo json_encode(Route::debugTree(), JSON_PRETTY_PRINT), "\n";
    break;
  case 'stats':
    echo json_encode(Route::stats(), JSON_PRETTY_PRINT), "\n";
    break;
  default:
    usage();
}