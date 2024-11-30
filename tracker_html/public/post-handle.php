<?php

include_once __DIR__ . '/../utils/MyDB.php';
include_once __DIR__ . '/../utils/MyCache.php';
include_once __DIR__ . '/../utils/Handle.php';
include_once __DIR__ . '/../utils/Agent.php';
include_once __DIR__ . '/../utils/Tools.php';

// Task of this script is to read from REDIS
// tokens list and store the tokens on the timeline
//
// start only when not already doing this task
// and continue until token list is empty

$redis = new MyCache();

// if ($redis->isHT('event')) {
//     $redis->close();
//     exit;
// }

$db = new MyDB();

$redis->htStart('event');
$inarow = 0;

$event = $redis->topEvent();
while ($event) {
    $redis->storeLog('Event: ' . $event);

    // Handle the atomic token...
    // Its a base64 encoded json

    list($encoded, $time) = explode('::', $event, 5);

    $json = urldecode(base64_decode($encoded));
    $data = json_decode($json);

    $visitor = $data->visitor;
    $session = $redis->labRead($visitor);
    $profile = findProfile($visitor);

    if ($profile) {
        trackEvent($profile, $session, $data);
    }

    $inarow++;

    // handle next
    $event = $redis->topEvent();
}

$redis->htEnd('event');
$redis->close();
$db->close();

exit;

// --
// -- HELPERS --
// --

function trackEvent($profile, $session, $data)
{
    global $db, $time, $encoded, $inarow;

    $payload = explodeHref($data->href);

    $count = ($inarow > 0) ? $inarow : $time;

    $cmne   = 'TEVT';
    $handle = Handle::create('time', $cmne, $time);

    $columns = [];

    $columns['handle']  = $handle;
    $columns['service'] = 'tracker';
    $columns['project'] = substr($profile, 0, 1);

    $columns['profile']    = $profile;
    $columns['session']    = $session;
    $columns['created_at'] = date('Y-m-d H:i:s');
    $columns['cmne']       = $cmne;
    $columns['time']       = $time;
    $columns['visitcode']  = Tools::visitCode($time);
    $columns['visitdate']  = Tools::visitDate($time);

    $columns['category'] = $data->category;
    $columns['action']   = $data->event;
    $columns['value']    = $data->value;

    $columns['url']      = $payload['url'];
    $columns['domain']   = $payload['domain'];
    $columns['path']     = $payload['path'];
    $columns['query']    = $payload['query'];
    $columns['fragment'] = $payload['fragment'];

    // Keepers
    $columns['large_1'] = $encoded;

    $db->insert('track_timelines', $columns);
}

function explodeHref($href)
{
    $payload['url']      = null;
    $payload['domain']   = null;
    $payload['path']     = null;
    $payload['query']    = null;
    $payload['fragment'] = null;

    if (isset($href)) {
        $hasQuery    = strpos($href, '?') !== false;
        $hasFragment = strpos($href, '#') !== false;

        $url = $href;

        if ($hasQuery && ! $hasFragment) {
            list($url, $query) = explode('?', $href, 2);
            extractPayload($query);
            $payload['query'] = $query;
        }
        if (! $hasQuery && $hasFragment) {
            list($url, $fragment) = explode('#', $href, 2);
            $payload['fragment']  = $fragment;
        }
        if ($hasQuery && $hasFragment) {
            list($url, $rest)       = explode('?', $href, 2);
            list($query, $fragment) = explode('#', $rest, 2);
            extractPayload($query);
            $payload['query']    = $query;
            $payload['fragment'] = $fragment;
        }
        list($proto, $rest)  = explode('//', $url);
        list($domain, $path) = explode('/', $rest, 2);
        // https://eventlab.com:5180/documentation/lifecycle

        $payload['url']    = $url;
        $payload['domain'] = explode(':', $domain)[0];
        $payload['path']   = $path;
    }
    return $payload;
}

function findProfile($visitor)
{
    global $db;

    $tokenstack = $db->findToken($visitor);
    return $tokenstack ? $tokenstack['profile'] : null;

}
