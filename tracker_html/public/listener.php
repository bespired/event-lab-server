<?php

set_time_limit(0);
ini_set('default_socket_timeout', -1);

include_once __DIR__ . '/../utils/MyCache.php';

function subscribe_handler($redis, $chan, $msg)
{
    echo $chan . ' ';

    switch ($chan) {
        case 'channel11':
            echo $msg . "---\n";
            break;
    }
}

$redis = new MyCache();
$redis->subscribe('subscribe_handler');

// SUBSCRIBE channel11 ch:00
