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

$redis->htStart('landing');

// Read Attributes of where table=tracker
// so name can be compared to aggrigate--/category/-/action/-/value/
// if in Attributes then do the aggrigation

$visit = $redis->topVisit();
while ($visit) {
    // file_put_contents('tmp.log', sprintf("%s %s\n", date('Y.m.d H:i:s'), $visit), FILE_APPEND);
    $redis->storeLog($visit);

    // Handle the atomic token...
    // split atomic token...

    list($visitor, $session, $time, $mode, $json) = explode('::', $visit, 5);

    $server     = json_decode($json);
    $contact    = null;
    $returncode = null;

    $payload = [];
    extractPayload($server->queryString);
    explodeHref();

    // Figure out the user agent and save the version
    $agent   = new Agent();
    $browser = $agent->browserAgent($server->userAgent);
    $agent->insertOnNew($db, $browser);

    // browser is for the device update
    $profile = findOrCreateProfileFromVisitorToken($db, $visitor, $browser);

    if (str_starts_with($mode, 'return')) {
        $db->increment('profiles', 'visitcount', $profile);
    }

    // or maybe we have a return token ...
    if (isset($payload['elrt'])) {
        $elrt  = $payload['elrt'];
        $parts = explode('-', $elrt);
        if (count($parts) === 4) {
            $returncode = $parts[3];
        }
        list($profile, $contact) = findByReturnCode($db, $elrt, $browser, $profile);
    }

    trackLanding();

    // TODO: UTMS entry

    // TODO: if it is a return token, then kick the return Journeys

    // Every visitor on the websocket might be too busy.
    // (new Socket())->send(json_encode(['visitor' => $visitor, 'time' => time()]));
    // $redis->tellChannel11('yet another visitor.');

    $redis->storeGeo($profile, $server->forwardedFor);
    $cmd = 'php track-geo.php > /dev/null 2>/dev/null &';
    shell_exec($cmd);

    $visit = $redis->topVisit();
}

$redis->htEnd('landing');
$redis->close();
$db->close();

exit;
// --
// -- HELPERS --
// --

function trackLanding()
{
    global $db, $time, $profile, $session, $visitor,
    $payload, $server, $browser, $contact;

    $cmne   = 'TLND';
    $handle = Handle::create('time', $cmne, $time);

    $columns = [];

    $columns['handle']  = $handle;
    $columns['service'] = 'tracker';
    $columns['project'] = substr($visitor, 0, 1);

    $columns['profile']    = $profile;
    $columns['session']    = $session;
    $columns['created_at'] = date('Y-m-d H:i:s');
    $columns['cmne']       = $cmne;
    $columns['time']       = $time;
    $columns['visitcode']  = Tools::visitCode($time);
    $columns['visitdate']  = Tools::visitDate($time);

    $columns['category'] = 'page';
    $columns['action']   = 'visit';
    $columns['value']    = $visitor;

    $columns['url']      = $payload['url'];
    $columns['domain']   = $payload['domain'];
    $columns['path']     = $payload['path'];
    $columns['query']    = $payload['query'];
    $columns['fragment'] = $payload['fragment'];

    $columns['attr_1'] = $server->forwardedFor;
    $columns['attr_2'] = $server->realIp;
    $columns['attr_3'] = $browser->hash;
    $columns['attr_4'] = $browser->device;
    $columns['attr_5'] = $browser->browser;
    $columns['attr_6'] = isset($returncode) ? $returncode : null;
    $columns['attr_7'] = isset($contact) ? $contact : null;

    // Keepers
    $columns['large_1'] = $server->queryString;

    $db->insert('track_timelines', $columns);
}

function findByReturnCode($db, $elrt, $browser, $profile)
{
    global $redis;

    $tokenstack = $db->findToken($elrt);

    if ($tokenstack) {
        $profile = $tokenstack['profile'];
        $contact = $tokenstack['contact'];
        updateVisit($db, $profile);

        $redis->storeLog("Found profile on return token.");
        // file_put_contents('tmp.log', "Found profile on return token.\n", FILE_APPEND);
    } else {
        $contact = null;
        // this is weird and could not happen ...
        // someone pulling us a leg?
    }

    return [$profile, $contact];
}

function findOrCreateProfileFromVisitorToken($db, $visitor, $browser)
{
    global $redis;

    $tokenstack = $db->findToken($visitor);

    if ($tokenstack) {
        $profile = $tokenstack['profile'];

        updateVisit($db, $profile);

        $redis->storeLog("Found profile on token.");
        // file_put_contents('tmp.log', "Found profile on token.\n", FILE_APPEND);
    } else {
        $profile = newVisit($db, $visitor, $browser);

        $redis->storeLog("Create profile and token.");
        // file_put_contents('tmp.log', "Create profile and token.\n", FILE_APPEND);
    }

    return $profile;
}

function updateVisit($db, $profile)
{
    global $time;

    $db->increment('profiles', 'pagecount', $profile);

    $columns = [];

    $columns['lastvistcode'] = Tools::visitCode($time);
    $columns['lastvistdate'] = Tools::visitDate($time);

    $db->update('profiles', $columns, ['handle' => $profile]);

}

function newVisit($db, $visitor, $browser)
{
    // ---
    // ---

    $time = time();

    $payload = [];
    $profile = Handle::create('profile', 'PBYV', $time);

    $payload['handle']        = $profile;
    $payload['cmne']          = 'PBYV';
    $payload['is_contact']    = 0;
    $payload['project']       = substr($visitor, 0, 1);
    $payload['visitcount']    = 1;
    $payload['pagecount']     = 1;
    $payload['firstvistcode'] = Tools::visitCode($time);
    $payload['firstvistdate'] = Tools::visitDate($time);
    $payload['firstdevice']   = $browser->device;
    $payload['lastvistcode']  = $payload['firstvistcode'];
    $payload['lastvistdate']  = $payload['firstvistdate'];
    $payload['lastdevice']    = $payload['firstdevice'];
    $payload['created_at']    = Tools::visitDate($time);

    $db->insert('profiles', $payload);

    $payload = [];

    $payload['handle']  = Handle::create('token', 'TBYV', $time);
    $payload['profile'] = $profile;
    $payload['contact'] = null;
    $payload['project'] = substr($visitor, 0, 1);
    $payload['token_1'] = $visitor;
    $payload['pointer'] = 2;

    $db->insert('track_tokens', $payload);

    return $profile;
}

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
}
