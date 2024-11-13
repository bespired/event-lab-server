<?php

class MyCache {
	private $servername;
	private $redis;

	public function __construct() {
		include_once 'Dot.php';
		$env = Dot::handle();

		if (!$env->redisHost) {
			echo "missing redisHost in env\n";
			exit;
		}

		$this->redis = new Redis();
		$this->redis->connect($env->redisHost, $env->redisPort);
		$this->redis->rawCommand('auth', 'default', $env->redisRootPassword);
	}

	public function close() {
		$this->redis->close();
	}

	public function labLogin($token, $payload) {
		$quotedKey = addslashes($token);
		$this->redis->setex($quotedKey, TTL, json_encode($payload));
	}

	public function labLogout($token) {
		$quotedKey = addslashes($token);
		$this->redis->del($quotedKey);
	}

	public function isLabLogin($token) {
		$quotedKey = addslashes($token);
		return $this->redis->exists($quotedKey);
	}

}
