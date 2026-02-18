<?php

$payload = serialize(['a' => 1]);
$decoded = unserialize($payload, ['allowed_classes' => false]);

return $decoded;
