<?php

class Logout {

	private $db;
	private $redis;
	private $project;
	private $contacts;
	private $attribs;

	public function __construct() {
		include_once __DIR__ . '/../utils/MyCache.php';
		include_once __DIR__ . '/../utils/Response.php';
		include_once __DIR__ . '/../utils/Jwt.php';

		$this->redis = new MyCache();
	}

	// what should logout do?
	public function handle($router) {

		$token = $router->headers->xToken;

		// remove from cache.
		if (!$this->redis->isLabLogin($token)) {
			Response::error('Not logged in.');
		}

		$this->redis->labLogout($token);
		Response::success('logout');

	}

}
