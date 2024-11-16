<?php

class Jwt {
	public $parsed;
	private $key = '-*!-Monkey-0*-';
	private $signature;

	public function __construct() {

		include_once "Dot.php";
		$env = Dot::handle();

		$this->key = $env->jwtPassword;

	}

	public function create($payload) {
		date_default_timezone_set('UTC');
		$time = time();

		$iss = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : 'localhost';

		$payload['iat'] = $time;
		$payload['exp'] = $time + TTL; //(60 * 60 * 12);
		$payload['iss'] = $iss;

		// $payload = [
		// 	'iat' => $time,
		// 	'uid' => $uid,
		// 	'exp' => $time + 60 * 60,
		// 	'iss' => 'localhost',
		// ];

		// Create token header as a JSON string
		$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

		// Create token payload as a JSON string
		$payload = json_encode($payload);

		// Encode Header to Base64Url String
		$base64UrlHeader = $this->encode($header);

		// Encode Payload to Base64Url String
		$base64UrlPayload = $this->encode($payload);

		// Create Signature Hash
		$signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $this->key, true);

		// Encode Signature to Base64Url String
		$base64UrlSignature = $this->encode($signature);

		// Create JWT
		return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
	}

	public function parse($token) {
		$header = $this->decode(explode('.', $token)[0] ?? '');
		$payload = $this->decode(explode('.', $token)[1] ?? '');
		$signature = $this->decode(explode('.', $token)[2] ?? '');

		$this->parsed = (object) [
			'header' => json_decode($header),
			'payload' => json_decode($payload),
		];
		$this->signature = $signature;

		return $this->parsed;
	}

	public function validate($token) {
		$this->parse($token);

		$base64UrlHeader = $this->encode(json_encode($this->parsed->header));
		$base64UrlPayload = $this->encode(json_encode($this->parsed->payload));
		$signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $this->key, true);

		return $this->signature == $signature;
	}

	public function expired($token) {
		$this->parse($token);

		return $this->parsed->payload->exp < time();
	}

	private function decode($encoded) {
		return base64_decode(str_replace(['-', '_', '~'], ['+', '/', '='], $encoded));
	}

	private function encode($string) {
		return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
	}
}
