<?php
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
	header("Access-Control-Allow-Headers: Authorization, Content-Type,Accept, Origin");
	header("Access-Control-Allow-Origin: *");

	exit(0);
}
