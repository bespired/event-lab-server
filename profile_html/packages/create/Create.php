<?php

Class Create {

	public static function handle($router) {

		switch ($router->action) {

		case 'attribute':

			switch ($router->pointer) {

			case 'group':
				include_once "AlterGroup.php";
				(new AlterGroup)->handle($router);
				break;

			case 'tag':
				include_once "AlterTag.php";
				(new AlterTag)->handle($router);
				break;

			case 'right':
				include_once "AlterRight.php";
				(new AlterRight)->handle($router);
				break;

			case 'consent':
				include_once "AlterConsent.php";
				(new AlterConsent)->handle($router);
				break;

			case 'field':
				include_once "AlterField.php";
				(new AlterField)->handle($router);
				break;

			case 'event':
				include_once "AlterEvent.php";
				(new AlterEvent)->handle($router);
				break;

			default:
				echo "Unknown pointer \"$router->pointer\".\n";
				break;
			}
			exit;

		default:

		}

	}

}