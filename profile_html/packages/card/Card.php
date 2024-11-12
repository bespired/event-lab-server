<?php

Class Card {

	public static function handle($router) {
		switch ($router->action) {
		case 'for':
			include_once __DIR__ . "/../profile/Profile.php";
			$profile = new Profile('a');

			$data = $profile->getProfileViaContact($router->pointer);

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($data);
			exit;

		default:
			echo "<html><body>ENV<br><pre><code>Unknown action";
			exit;
		}
	}

}