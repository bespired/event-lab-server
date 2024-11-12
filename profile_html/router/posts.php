<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	include_once "Router.php";

	$router = new Router();

	switch ($router->package) {
	case 'create':
		include_once __DIR__ . "/../packages/create/Create.php";
		Create::handle($router);
		break;

	case 'login':
		include_once __DIR__ . "/../packages/login/Login.php";
		(new Login())->handle($router);
		break;

	}

	exit(0);

}
