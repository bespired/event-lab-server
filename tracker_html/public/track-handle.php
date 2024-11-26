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
    file_put_contents('tmp.log', sprintf("%s %s\n", date('Y.m.d H:i:s'), $visit), FILE_APPEND);

    // Handle the atomic token...
    // split atomic token...

    list($visitor, $session, $time, $json) = explode('::', $visit, 4);

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

    $redis->tellChannel11('yet another visitor.');

    $visit = $redis->topVisit();
}

// TODO: if log gets too long trunk it.
// $logsToKeep = file_get_contents('tmp . log', null, null, -125000, 125000);
// file_put_contents('tmp . log', $logsToKeep);

$redis->htEnd('landing');
$redis->close();
$db->close();

exit;
// --''

function geoData()
{
    global $db, $server;

    $ip = $server->forwardedFor;
    if ($ip === '192.168.65.1') {
        $ip = '77.161.128.35';
    }

    // db has dotenv for convenience

    $geotoken = $db->env->geolocationDb;
    $url      = "https://geolocation-db.com/json/$geotoken/$ip";

    $geojson = file_get_contents($url);
    $geo     = json_decode($geojson);

    return $geo;
}

// function geoLanding()
// {
//     global $db, $time, $geodata, $profile, $session, $visitor, $payload, $server;

//     $cmne   = 'GLND';
//     $handle = Handle::create('time', $cmne, $time);

//     $slots = [];

//     $slots['handle']  = $handle;
//     $slots['service'] = 'tracker';
//     $slots['project'] = substr($visitor, 0, 1);

//     $slots['profile']    = $profile;
//     $slots['session']    = $session;
//     $slots['created_at'] = date('Y-m-d H:i:s');
//     $slots['cmne']       = $cmne;
//     $slots['time']       = $time;
//     $slots['visitcode']  = Tools::visitCode($time);
//     $slots['visitdate']  = Tools::visitDate($time);

//     $slots['category'] = 'page';
//     $slots['action']   = 'geo';
//     $slots['value']    = $visitor;

//     $slots['url']      = $payload['url'];
//     $slots['domain']   = $payload['domain'];
//     $slots['path']     = $payload['path'];
//     $slots['query']    = $payload['query'];
//     $slots['fragment'] = $payload['fragment'];

//     $slots['attr_1'] = $server->forwardedFor;
//     $slots['attr_2'] = $geodata->country_code;
//     $slots['attr_3'] = $geodata->country_name;
//     $slots['attr_4'] = $geodata->state;
//     $slots['attr_5'] = $geodata->city;
//     $slots['attr_6'] = $geodata->postal;
//     $slots['attr_7'] = $geodata->latitude;
//     $slots['attr_8'] = $geodata->longitude;

//     // Keepers
//     $slots['large_1'] = json_encode($geodata);

//     $db->insert('track_timelines', $slots);
// }

function trackLanding()
{
    global $db, $time, $profile, $session, $visitor,
    $payload, $server, $browser, $contact;

    $cmne   = 'TLND';
    $handle = Handle::create('time', $cmne, $time);

    $slots = [];

    $slots['handle']  = $handle;
    $slots['service'] = 'tracker';
    $slots['project'] = substr($visitor, 0, 1);

    $slots['profile']    = $profile;
    $slots['session']    = $session;
    $slots['created_at'] = date('Y-m-d H:i:s');
    $slots['cmne']       = $cmne;
    $slots['time']       = $time;
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
    $slots['attr_6'] = isset($returncode) ? $returncode : null;
    $slots['attr_7'] = isset($contact) ? $contact : null;

    // Keepers
    $slots['large_1'] = $server->queryString;

    $db->insert('track_timelines', $slots);
}

function findByReturnCode($db, $elrt, $browser, $profile)
{
    $tokenstack = $db->findToken($elrt);

    if ($tokenstack) {
        $profile = $tokenstack['profile'];
        $contact = $tokenstack['contact'];
        updateVisit($db, $profile, $browser);

        file_put_contents('tmp.log', "Found profile on return token.\n", FILE_APPEND);
    } else {
        $contact = null;
        // this is weird and could not happen ...
        // someone pulling us a leg?
    }

    return [$profile, $contact];
}

function findOrCreateProfileFromVisitorToken($db, $visitor, $browser)
{
    $tokenstack = $db->findToken($visitor);

    if ($tokenstack) {
        $profile = $tokenstack['profile'];
        updateVisit($db, $profile, $browser);

        file_put_contents('tmp.log', "Found profile on token.\n", FILE_APPEND);
    } else {
        $profile = newVisit($db, $visitor, $browser);

        file_put_contents('tmp.log', "Create profile and token.\n", FILE_APPEND);
    }

    return $profile;
}

function updateVisit($db, $profile, $browser)
{
    $db->increment('profiles', 'visitcount', $profile);
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

        if ($hasQuery && !$hasFragment) {
            list($url, $query) = explode('?', $href, 2);
            extractPayload($query);
            $payload['query'] = $query;
        }
        if (!$hasQuery && $hasFragment) {
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
