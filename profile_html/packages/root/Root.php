<?php

Class Root {

	public static function handle($router) {
		switch ($router->action) {
		case 'info':
			opcache_reset();
			phpinfo();
			exit;

		case 'router':
			echo "<html><body>INFO<br><pre><code>";
			print_r($router);
			exit;

		case 'env':
			echo "<html><body>ENV<br><pre><code>";
			print_r(getenv());
			exit;

		default:
		}
	}

}