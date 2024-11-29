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
    $mode    = 'in-a-session';

    // $session === 'undefined' is a creepy error...
    if ((! $session) || (! validToken($session))) {
        $session = Token::createSession();
        $mode    = 'in-a-new-session';
    }

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

            $elrt    = $payload['elrt'];
            $visitor = getIdFromElrt($elrt);
            $session = Token::createSession();

            $mode = 'first-contact';
        }
    }

    if (! isset($payload['first'])) {

        $visitor = $payload['visitor'];
        if ($redis->isLabStored($visitor)) {
            $session = $redis->labRead($visitor);
        }

        if ((! $session) || (! validToken($session))) {
            $session = Token::createSession();
            // new session on returning profile
            // update visitcount on profile.
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

// store session token
$redis->labWrite($visitor, $session);

// PUT ON REDIS TO HANDLE ...
$server = [];
$items  = [
    'userAgent'    => 'HTTP_USER_AGENT',
    'queryString'  => 'QUERY_STRING',
    'forwardedFor' => 'HTTP_X_FORWARDED_FOR',
    'realIp'       => 'HTTP_X_REAL_IP',
];
foreach ($items as $key => $itemname) {
    $server[$key] = isset($_SERVER[$itemname]) ? $_SERVER[$itemname] : null;
}

$redis->storeVisit($visitor, $session, $mode, json_encode($server));
$cmd = 'php track-handle.php > /dev/null 2>/dev/null &';
shell_exec($cmd);

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

// elrt should be in token fields...
// the first 3 sylables should fit as token.

function getIdFromElrt($elrt)
{
    $parts = explode('-', $elrt);
    array_pop($parts);
    return join('-', $parts);
}

function validToken($token)
{
    $parts = explode('-', $token);
    return count($parts) > 1;
}

function extractPayload($query)
{
    global $payload;
    $valuepairs = explode('&', $query);

    foreach ($valuepairs as $valuepair) {
        list($key, $value) = explode('=', $valuepair, 2);
        $payload[$key]     = $value;
    }
}
