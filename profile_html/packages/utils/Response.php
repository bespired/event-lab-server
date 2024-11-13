<?php

class Response {
	public static function error($message) {
		header('Content-Type: application/json');
		header("Access-Control-Allow-Origin: *");

		echo json_encode([
			'error' => true,
			'status' => 'error',
			'message' => $message,
		]);
		exit;
	}

	public static function success($message, $token = null) {
		header('Content-Type: application/json');
		header("Access-Control-Allow-Origin: *");

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
