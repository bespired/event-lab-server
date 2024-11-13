<?php

class Response {

	private static function cors() {
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
		header("Access-Control-Allow-Origin:*");
		header("Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Auth-Token");
		header('Content-Type: application/json');
	}

	public static function error($message) {
		self::cors();

		echo json_encode([
			'error' => true,
			'status' => 'error',
			'message' => $message,
		]);
		exit;
	}

	public static function success($message, $token = null) {
		self::cors();

		$response = [
			'success' => true,
			'status' => 'success',
			'message' => $message,
		];
		if ($token) {
			$response['token'] = $token;
		}

		echo json_encode($response);
		exit;
	}
}
