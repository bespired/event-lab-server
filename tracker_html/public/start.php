<?php
// Task of this script is to return a storage token,
// and a session token.

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Auth-Token");

header('Content-Type: application/json');

$response = [
    'version' => '1.0',
];

echo json_encode($response);
