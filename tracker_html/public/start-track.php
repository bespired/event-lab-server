<?php

include_once __DIR__ . '/../utils/globs.php';
include_once __DIR__ . '/../utils/MyDB.php';
include_once __DIR__ . '/../utils/MyCache.php';
include_once __DIR__ . '/../utils/Token.php';

// Task of this script is to return a storage token,
// and a session token.

// Lets find out who we are dealing with here

$payload = [];
$cmd     = null;

extractPayload($_SERVER['QUERY_STRING']);

if (isset($payload['href'])) {
    $href        = urldecode(base64_decode($payload['href']));
    $hasQuery    = strpos($href, '?') !== false;
    $hasFragment = strpos($href, '#') !== false;

    if ($hasQuery && ! $hasFragment) {
        list($url, $query) = explode('?', $href, 2);
        extractPayload($query);
    }
    if (! $hasQuery && $hasFragment) {
        list($url, $fragment) = explode('#', $href, 2);
    }
    if ($hasQuery && $hasFragment) {
        list($url, $rest)       = explode('?', $href, 2);
        list($query, $fragment) = explode('#', $rest, 2);
        extractPayload($query);
        $payload['fragment'] = $fragment;
    }
}

// $sets = [
//     'first'   => 'first',    // first visit ever
//     'visitor' => 'visitor',  // returning visit
//     'session' => 'session',  // in same session
//     'elrt'    => 'contact',  // landing with elrt
// ];

$redis = new MyCache();

$response = [
    'version' => '1.0',
];

$visitor = null;
$session = null;
$mode    = 'unknown';

if (isset($payload['session'])) {
    // if session, then we did all the token stuff
    // just log the landing and return the tokens...

    $visitor = $payload['visitor'];
    $session = $payload['session'];

    $mode = 'in-a-session';
} else {
    if (isset($payload['first'])) {
        if (! isset($payload['elrt'])) {
            // if first and no contact ...
            // then lets create a token and session for this profile...

            $visitor = Token::createVisitor();
            $session = Token::createSession();

            $mode = 'first-no-contact';
        } else {
            // if first and contact ...
            // then lets get a token and session for this profile...

            $visitor = 'get-id-from-db';
            $session = Token::createSession();

            $mode = 'first-contact';
        }
    }

    if (! isset($payload['first'])) {

        $visitor = $payload['visitor'];
        if ($redis->isLabStored($visitor)) {
            $session = $redis->labRead($visitor);
        }
        if ((! $session) || ($session === 'null')) {
            $session = Token::createSession();
        }

        if (! isset($payload['elrt'])) {
            // if returning and no contact ...
            // then lets get a session token for this profile...
            // maybe it's just another page ...

            $mode = 'return-no-contact';
        } else {
            // if returning and contact ...
            // then maybe we can join profiles...
            // because... this might be another device...

            $mode = 'return-contact';
        }
    }
}

// PUT ON REDIS TO HANDLE ...
//  $redis->storeSession($visitor, $session);
//  $cmd = 'php start-handle.php > /dev/null 2>/dev/null &';
//  shell_exec($cmd);

file_put_contents('tmp.log',
    sprintf("%s %s %s %s %s \n",
        date('Y.m.d H:i:s'), $visitor, $session, $mode, json_encode($_SERVER)),
    FILE_APPEND | LOCK_EX);

$redis->labWrite($visitor, $session);

$redis->close();
// ---
// And there we have it

$response['session'] = $session;
$response['mode']    = $mode;
if ($visitor) {
    $response['visitor'] = $visitor;
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Auth-Token');
header('Content-Type: application/json');

echo json_encode($response);

exit;

// ---
// ---
// ---

function extractPayload($query)
{
    global $payload;
    $valuepairs = explode('&', $query);

    foreach ($valuepairs as $valuepair) {
        list($key, $value) = explode('=', $valuepair, 2);
        $payload[$key]     = $value;
    }
}
