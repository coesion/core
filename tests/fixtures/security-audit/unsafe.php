<?php

$payload = 'a:1:{s:1:"x";s:1:"y";}';
exec('echo unsafe');
$decoded = unserialize($payload);

return $decoded;
