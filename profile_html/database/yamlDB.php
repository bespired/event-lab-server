<?php

// YES I KNOW

include_once "../packages/utils/Dot.php";
$env = Dot::handle();

$servername = $env->mysqlHost;
$username = $env->mysqlRootUser;
$password = $env->mysqlRootPassword;
$database = $env->mysqlDatabase;

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

// Check database
$query = 'SHOW DATABASES LIKE "' . $database . '"';
$resExists = $conn->query($query);

if ($resExists->num_rows !== 1) {

	echo "Database does not exists.\n";
	exit;

}

$tablefiles = glob('./tables/*.yaml');
foreach ($tablefiles as $table) {
	$name = str_replace('.yaml', '', explode('/', $table)[2]);
	$file = file_get_contents($table);
	$parsed = (object) yaml_parse($file);
	if (isset($parsed->seeds)) {
		$tables[$name] = $parsed;
	}
}

$conn->query('use ' . $database);

foreach ($tables as $table) {
	$sql = "DESCRIBE `$table->tablename`";
	$result = $conn->query($sql);

	echo "Table $table->tablename\n";

	$rows = [];
	$data = [];

	while ($row = $result->fetch_assoc()) {
		$low = array_change_key_case($row);
		$rows[] = $low;
		$data[$low['field']] = null;
	}

	$sql = "SELECT * FROM `$table->tablename`";
	$result = $conn->query($sql);

	if ($result->num_rows == 0) {
		continue;
	}

	$date = strtolower(date('d-F-Y--H-m-s'));

	$filename = "./seeds/$table->tablename.yaml";
	$movename = "./seeds/backup/$table->tablename-$date.yaml";

	rename($filename, $movename);
	file_put_contents($filename, '');

	while ($row = $result->fetch_assoc()) {
		$row['id'] = '(auto)';

		$yaml = yaml_emit($row);
		file_put_contents($filename, $yaml, FILE_APPEND);
	}

	// remove ... between documents
	$file = file_get_contents($filename);
	$file = str_replace("...\n---", "---", $file);
	file_put_contents($filename, $file);

}

$conn->close();
