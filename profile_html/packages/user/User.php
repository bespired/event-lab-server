<?php

include_once __DIR__ . '/../traits/Contacts.php';
include_once __DIR__ . '/../traits/Projects.php';
include_once __DIR__ . '/../traits/Access.php';

class User {

	use Contacts;
	use Projects;
	use Access;

	private $db;
	private $redis;
	private $project;
	private $contacts;
	private $attribs;

	public function __construct() {
		include_once __DIR__ . '/../utils/MyDB.php';
		include_once __DIR__ . '/../utils/MyCache.php';
		include_once __DIR__ . '/../utils/Response.php';
		include_once __DIR__ . '/../utils/Jwt.php';

		$this->db = new MyDB();
		$this->redis = new MyCache();
	}

	public function handle($router) {

		// if payload has an email or handle then get that one.
		// if not assume we want user from token.

		// the route protector puts contact handle from Jwt in router data

		$header = $router->headers;
		$name = isset($header->xForwardedHost) ? $header->xForwardedHost : 'eventlab';

		// user should be in sys_accesses
		// or as a contact or as a clone
		// handle, contact, clone, role, project

		$accesses = $this->loadAccess($router->contact, 'backend', true);

		// is any of these on this domain/project, then thats our user
		$project = $router->projectChar;
		$found = array_filter($accesses, function ($a) use ($project) {return $a->project === $project;});

		// but if not, we need to check for clones
		if (!$found) {
			$clones = $this->loadClone($router->contact, $project);
			if (isset($clones[$project])) {$found = $clones[$project];}
		}

		// still none found then no user
		if (!$found) {
			$user = [
				'name' => 'Anonymous',
				'role' => 'none',
				'settings' => [
					'colorscheme' => 'light',
				],
			];

			Response::error($user);
		}

		if (is_array($found)) {$found = reset($found);}

		$contact = $this->loadContacts($found->contact);
		$user = (object) $contact[0];

		$user = [
			'project' => $user->project,
			'name' => $user->firstname . ' ' . $user->lastname,
			'role' => $user->role,
			'profile' => $user->profile,
			'contact' => $user->handle,
			'email' => $user->email,
			'settings' => [
				'colorscheme' => 'light',
			],
		];

		Response::success($user);
	}
}
