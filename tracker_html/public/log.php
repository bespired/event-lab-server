<?php

include_once __DIR__ . '/../utils/MyDB.php';
include_once __DIR__ . '/../utils/MyCache.php';
include_once __DIR__ . '/../utils/Handle.php';
include_once __DIR__ . '/../utils/Tools.php';

// Task of this script is to read from REDIS
// and to give back a log of what is happening

$redis = new MyCache();

$log = [
    'version' => '1.0',

    'lists'   => [
        'pixels' => $redis->llen('tokens'),
        'visits' => $redis->llen('visits'),
    ],

];

$redis->close();

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Auth-Token");
header('Content-Type: application/json');

echo json_encode($log);
