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

if ($redis->isHT('landing')) {
    $redis->close();
    exit;
}

$db = new MyDB();

// $redis->htStart('landing');

// Read Attributes of where table=tracker
// so name can be compared to aggrigate--/category/-/action/-/value/
// if in Attributes then do the aggrigation

$visit = $redis->topVisit();
while ($visit) {
    file_put_contents('tmp.log', sprintf("%s %s\n", date('Y.m.d H:i:s'), $visit), FILE_APPEND);

    // Handle the atomic token...
    // split atomic token...
    list($visitor, $session, $time, $json) = explode('::', $visit, 4);

    $server = json_decode($json);

    $payload = [];
    extractPayload($server->queryString);
    explodeHref();

    // Figure out the user agent and save the version
    $agent   = new Agent();
    $browser = $agent->browserAgent($server->userAgent);
    $agent->insertOnNew($db, $browser);

    // $profile = findOrCreateProfileFromVisitorToken($visitor);
    // or maybe we have a return token ...
    $profile = $visitor;

    $cmne = 'TLND';

    $handle = Handle::create('time', $cmne, $time);

    $slots['handle']  = $handle;
    $slots['service'] = 'tracker';
    $slots['project'] = substr($visitor, 0, 1);

    $slots['profile']    = $profile;
    $slots['session']    = $session;
    $slots['created_at'] = date('Y-m-d H:i:s');
    $slots['cmne']       = $cmne;
    $slots['visitcode']  = Tools::visitCode($time);
    $slots['visitdate']  = Tools::visitDate($time);

    $slots['category'] = 'page';
    $slots['action']   = 'visit';
    $slots['value']    = $visitor;

    $slots['url']      = $payload['url'];
    $slots['domain']   = $payload['domain'];
    $slots['path']     = $payload['path'];
    $slots['query']    = $payload['query'];
    $slots['fragment'] = $payload['fragment'];

    $slots['attr_1'] = $server->forwardedFor;
    $slots['attr_2'] = $server->realIp;
    $slots['attr_3'] = $browser->hash;
    $slots['attr_4'] = $browser->device;
    $slots['attr_5'] = $browser->browser;

    // UTMS

    // Keepers
    $slots['large_1'] = $server->queryString;

    $db->insert('track_timelines', $slots);

    $visit = $redis->topVisit();
}

// TODO: if log gets too long trunk it.
// $logsToKeep = file_get_contents('tmp . log', null, null, -125000, 125000);
// file_put_contents('tmp . log', $logsToKeep);

$redis->htEnd('landing');
$redis->close();
$db->close();

exit;
// --

function extractPayload($query)
{
    global $payload;
    $valuepairs = explode('&', $query);

    foreach ($valuepairs as $valuepair) {
        if (strpos($valuepair, '=')) {
            list($key, $value) = explode('=', $valuepair, 2);
            $payload[$key]     = $value;
        } else {
            $payload[$valuepair] = true;

        }
    }

}

function explodeHref()
{
    global $payload;
    $payload['url']      = null;
    $payload['domain']   = null;
    $payload['path']     = null;
    $payload['query']    = null;
    $payload['fragment'] = null;

    if (isset($payload['href'])) {
        $href        = urldecode(base64_decode($payload['href']));
        $hasQuery    = strpos($href, ' ? ') !== false;
        $hasFragment = strpos($href, ' #') !== false;

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
}
