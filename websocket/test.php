<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require __DIR__ . '/vendor/autoload.php';

$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyb2wiOiJhOnN1cGVyLWFkbWluO2I6ZWRpdG9yIiwiaGRsIjoiYTAzdWwteXZsekpaNDQiLCJpYXQiOjE3MzEyNDA3NTEsImV4cCI6MTczMTI0NDM1MSwiaXNzIjoibG9jYWxob3N0In0.6oRY_J7aKVjUKUxNllME00v6Ch_j5UqMuzGcmMxS9e8';

print_r($token);

$key = '-*!-Goat-0*-';

try {

	$decoded = JWT::decode($token, new Key($key, 'HS256'));

} catch (Exception $e) {

	$decoded = null;
	if ($e->getMessage() == "Expired token") {
		list($header, $payload, $signature) = explode(".", $token);
		$decoded = json_decode(base64_decode($payload));
		// So how long expired ???
		// And does that still count as valid?
	}

}
print_r($decoded);

// if (!in_array($token, $this->secrets)) {
// 	// is it a valid one?
// 	$key = '-*!-Goat-0*-';
// 	$decoded = JWT::decode($token, new Key($key, 'HS256'));
// 	print_r($decoded);
// }