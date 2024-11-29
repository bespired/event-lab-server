<?php

include_once __DIR__ . '/../utils/MyCache.php';

$redis = new MyCache();

$redis->storeGeo('a49xn-prof-7rlJCO34', '77.161.128.35');

// $cmd = 'php track-geo.php > /dev/null 2>/dev/null &';
// shell_exec($cmd);

$redis->close();
