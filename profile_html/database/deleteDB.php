<?php

// YES I KNOW

include_once "../packages/utils/Dot.php";
$env = Dot::handle();

$servername = $env->mysqlHost;
$username = $env->mysqlRootUser;
$password = $env->mysqlRootPassword;
$database = $env->mysqlDatabase;

// Create connection
$conn = new mysqli($servername, $username, $password);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

// Check database
$query = 'SHOW DATABASES LIKE "' . $database . '"';
$resExists = $conn->query($query);

if ($resExists->num_rows == 1) {

	// Create database
	$sql = "DROP DATABASE $database";
	if ($conn->query($sql) === TRUE) {
		echo "Database deleted successfully.\n";
	}
} else {

	echo "Database does not exists.\n";

}

$conn->close();
