<?php
Class Router {

	public $service;
	public $method;
	public $projectId;
	public $projectChar;
	public $package;
	public $action;
	public $pointer;
	public $payload;
	public $query;
	public $headers;
	public $cookies;
	public $contact;
	public $uri;

	// all need to work:
	// https://localhost/--/profile/0/info
	// https://localhost/--/profile/info

	public function protected($needed = null) {

		if (!isset($this->headers->xAuthToken)) {
			http_response_code(401);
			echo "No token, no entry";
			exit;
		}

		include __DIR__ . '/../packages/utils/Jwt.php';
		$token = $this->headers->xAuthToken;
		$jwt = new JWT();

		$validates = $jwt->validate($token);

		if (!$validates) {
			http_response_code(401);
			echo "Wrong token, no entry";
			exit;
		}

		$expired = $jwt->expired($token);

		if ($expired) {
			http_response_code(401);
			echo "Old token, no entry";
			exit;
		}

		$parsed = $jwt->parse($token);

		$contact = $parsed->payload->hdl;

		$parts = explode('-', $contact);
		$this->contact = sprintf('%s-cont-%s', $parts[0], $parts[1]);

	}

	public function __construct() {

		$this->service = 'profile';
		$this->package = 'root';
		$this->method = strtolower($_SERVER['REQUEST_METHOD']);
		$this->uri = strtolower($_SERVER['REQUEST_URI']);
		$this->query = urldecode($_SERVER['QUERY_STRING']);

		if (!str_starts_with($this->uri, '/--')) {
			return;
		}

		$chop = str_starts_with($this->uri, '/--/profile') ? 12 : 4;
		$api = substr($_SERVER['REDIRECT_URL'], $chop);
		$parts = explode('/', urldecode($api));

		$this->projectId = is_numeric($parts[0]) ? intval($parts[0]) : null;

		if ($this->projectId !== null) {
			array_shift($parts);
		} else {
			$this->projectId = 0;
		}

		$this->projectChar = chr(97 + $this->projectId);

		// get Headers
		if (count(getallheaders())) {
			$this->headers = (object) [];
		}

		foreach (getallheaders() as $key => $value) {

			$name = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($key)))));
			$this->headers->$name = $value;
		}

		// extract Cookies
		if ($this->headers && isset($this->headers->cookie)) {
			$this->cookies = (object) [];

			$cookies = $this->headers->cookie;
			$pairs = explode(';', $cookies);

			foreach ($pairs as $pair) {
				$name = trim(explode('=', $pair)[0]);
				$value = trim(explode('=', $pair)[1]);
				$this->cookies->$name = $value;
			}

			unset($this->headers->cookie);
		}

		if (count($parts) === 1) {
			$this->action = $parts[0];
			return;
		}

		$this->package = array_shift($parts);
		$this->action = array_shift($parts);
		$this->pointer = join('/', $parts);

		if ($this->method === 'post' && isset($this->headers->contentType)) {
			$errors = [
				JSON_ERROR_NONE => 'No error',
				JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
				JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
				JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
				JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
				JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
			];

			$payload = file_get_contents("php://input");
			$this->payload = (object) ["data" => $payload];

			if (str_ends_with($this->headers->contentType, 'json')) {
				$json = json_decode($payload);
				$jle = json_last_error();

				$this->payload = (!$jle) ? $json : (object) ["jsonerror" => $errors[$jle]];
			}
		}
	}

}
