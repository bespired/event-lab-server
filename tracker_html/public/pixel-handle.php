<?php

include_once __DIR__ . '/../utils/MyDB.php';
include_once __DIR__ . '/../utils/MyCache.php';
include_once __DIR__ . '/../utils/Handle.php';
include_once __DIR__ . '/../utils/Tools.php';

// Task of this script is to read from REDIS
// tokens list and store the tokens on the timeline
//
// start only when not already doing this task
// and continue until token list is empty

$redis = new MyCache();

if ($redis->isHT()) {
    $redis->close();
    exit;
}

$db = new MyDB();

$redis->htStart();

// Read Attributes of where table=tracker
// so name can be compared to aggrigate--/category/-/action/-/value/
// if in Attributes then do the aggrigation

$atomic = $redis->topToken();
while ($atomic) {
    file_put_contents('tmp.log', sprintf("%s %s\n", date('Y.m.d H:i:s'), $atomic), FILE_APPEND | LOCK_EX);

    // Handle the atomic token...
    // split atomic token...
    list($token, $category, $action, $time) = explode(':', $atomic);

    // split the token...
    $profile = $token;
    if (strpos($token, '--')) {
        list($profile, $partial) = explode('--', $token);
    }
    // find the token for the profile ... etc.
    $cmne = 'T' . strtoupper(substr($category, 0, 1) . substr($action, 0, 2));

    $handle              = Handle::create('time', $cmne, $time);
    $slots['handle']     = $handle;
    $slots['service']    = 'tracker';
    $slots['project']    = substr($profile, 0, 1);
    $slots['profile']    = $profile;
    $slots['created_at'] = date('Y-m-d H:i:s');
    $slots['cmne']       = $cmne;
    $slots['visitcode']  = Tools::visitCode($time);
    $slots['visitdate']  = Tools::visitDate($time);

    $slots['category'] = $category;
    $slots['action']   = $action;
    $slots['value']    = $partial;

    $db->insert('track_timelines', $slots);

    // store mail open in accu_time_mails of results_write table if used in a panel...
    // So TODO after panels are done.
    // Same trick as with pixel? throw in a handle Redis List?

    // add to aggrigate? is it in attributes ?
    // service::category-action-value
    // tracker-mail-pixel-a1
    // So TODO after aggrigations are done?
    // Same trick as with pixel? throw in a handle Redis List?

    $atomic = $redis->topToken();
}

// if log gets too long trunk it.
// $logsToKeep = file_get_contents('tmp.log', null, null, -125000, 125000);
// file_put_contents('tmp.log', $logsToKeep);

$redis->htEnd();
$redis->close();
$db->close();
