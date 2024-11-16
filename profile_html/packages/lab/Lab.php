<?php

include_once __DIR__ . '/../traits/Attributes.php';
include_once __DIR__ . '/../traits/Roles.php';

class Lab {

	use Attributes;
	use Roles;

	private $db;
	private $redis;
	private $project;
	private $contacts;
	private $attribs;

	public function __construct() {
		include_once __DIR__ . '/../utils/MyDB.php';
		include_once __DIR__ . '/../utils/MyCache.php';
		include_once __DIR__ . '/../utils/Response.php';

		$this->db = new MyDB();
		$this->redis = new MyCache();
	}

	public function handle($router) {

		switch ($router->action) {
		case 'cruds':
			$this->cruds($router);
			break;

		default:
			Response::error('no such action in lab ' . $router->action);
			break;
		}
	}

	private function cruds($router) {
		// cruds based on roles

		if (!isset($router->payload->roles) || !count($router->payload->roles)) {
			Response::error('No roles in payload');
		}

		$attrs = $this->loadAccessAttributes();

		$keys = array_keys($attrs);
		$asis = array_map(fn($a) => explode('--', $a['name'])[1], $attrs);
		$selects = array_combine($keys, $asis);

		$roles = $router->payload->roles;

		// different project have different roles?
		// maybe later...

		$cruds = $this->loadCruds($roles, $selects);

		Response::success($cruds);

	}
}
