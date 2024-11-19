<?php
// Task of this script is to read from REDIS
// tokens list and store the tokens on the timeline
//
// start only when not already doing this task
// and continue until token list is empty

include_once "../utils/MyCache.php";

$redis = new MyCache();

if ($redis->isHT()) {
    $redis->close();
    exit;
}

$redis->htStart();
$atomic = $redis->topToken();
while ($atomic) {
    file_put_contents('tmp.log', sprintf("%s %s\n", date("Y.m.d H:i:s"), $atomic), FILE_APPEND | LOCK_EX);

    // Handle the atomic token...
    // split atomic token...
    list($token, $category, $action, $time) = explode(":", $atomic);

    // split the token...
    list($prof, $partial) = explode("--", $token);

    // find the token for the profile ... etc.

    $atomic = $redis->topToken();
}

// if log gets too long trunk it.
// $logsToKeep = file_get_contents('tmp.log', null, null, -125000, 125000);
// file_put_contents('tmp.log', $logsToKeep);

$redis->htEnd();
$redis->close();
