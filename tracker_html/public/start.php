<?php
// Task of this script is to return a storage token,
// and a session token.

// Lets find out who we are

$payload = [];
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
    }
}

$sets = [
    'first' => 'first',     // first visit ever
    'return' => 'return',   // returning visit
    'session' => 'session', // in same session
    'elrt' => 'contact',    // landing with elrt
];

$response = [
    'version' => '1.0',
];

// if session, then we did all the token stuff
// just log the landing and return the tokens...
if (isset($payload['session'])) {

    // $response['session'] = $payload['session'];
    $response['mode']       = 'in-a-session';
    $response['session-is'] = $payload['session'];

} else {

    if (isset($payload['first'])) {

        if (isset($payload['elrt'])) {
            // if first and contact ...
            // then lets get a token and session for this profile...

            $response['token']   = 'create-a-visitor-token';
            $response['session'] = 'create-a-session-token';
            $response['mode']    = 'first-contact';

        } else {
            // if first and no contact ...
            // then lets get a token and session for this profile...

            $response['token']   = 'create-a-visitor-token';
            $response['session'] = 'create-a-session-token';
            $response['mode']    = 'first-no-contact';
        }

    }

    if (isset($payload['return'])) {
        if (isset($payload['elrt'])) {
            // if returning and contact ...
            // then maybe we can join profiles...
            // because... this might be another device...

            $response['session'] = 'create-a-session-token';
            $response['mode']    = 'return-contact';

        } else {
            // if returning and no contact ...
            // then lets get a session token for this profile...

            $response['session'] = 'create-a-session-token';
            $response['mode']    = 'return-no-contact';
        }

    }
}

// PUT ON REDIS TO HANDLE

// ---
// And there we have it

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Auth-Token");
header('Content-Type: application/json');

echo json_encode($response);

exit;
// ---

function extractPayload($query)
{
    global $payload;
    $valuepairs = explode("&", $query);
    foreach ($valuepairs as $valuepair) {
        list($key, $value) = explode('=', $valuepair, 2);
        $payload[$key]     = $value;
    }
}
