<?php

include_once __DIR__ . '/../utils/MyCache.php';

// Task of this script is to store tracking data,
// from the track script ( called start to fool chrome )

$redis = new MyCache();

$redis->storeEvent($_SERVER['QUERY_STRING']);
$cmd = 'php post-handle.php > /dev/null 2>/dev/null &';
shell_exec($cmd);

$redis->close();

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Auth-Token');

http_response_code(204);

exit;
