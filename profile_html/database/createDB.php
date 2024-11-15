<?php

// YES I KNOW

$tpl = [
	'incremental' => ' `{name}` BIGINT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY',
	'int' => ' `{name}` INT DEFAULT 0',
	'bit' => ' `{name}` TINYINT(1) DEFAULT 0',
	'base1' => ' `{name}` CHAR(1) DEFAULT "-"', // - 0 1 | S F R | P | ...
	'base1-null' => ' `{name}` CHAR(1) DEFAULT NULL',
	'date-code' => ' `{name}` CHAR(6) DEFAULT NULL', // date-code day-of-year - year : 142-24
	'time-code' => ' `{name}` CHAR(9) DEFAULT NULL', // time-code day-of-year - year - hour : 142-24-15
	'base26' => ' `{name}` CHAR(1) NOT NULL',
	'mnemonic' => ' `{name}` CHAR(4) DEFAULT NULL',
	'crud' => ' `{name}` CHAR(4) DEFAULT NULL',
	'boolean' => ' `{name}` TINYINT DEFAULT false',
	'handle' => ' `{name}` VARCHAR(26) NOT NULL',
	'handle-null' => ' `{name}` VARCHAR(26) DEFAULT NULL',
	'concat' => ' `{name}` VARCHAR(512) DEFAULT ""',
	'name' => ' `{name}` VARCHAR(128) DEFAULT NULL',
	'large' => ' `{name}` LONGTEXT',
	'label' => ' `{name}` VARCHAR(128) DEFAULT "New Label"',
	'data' => ' `{name}` BLOB',
	'timestamp' => ' `{name}` TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
	'timestamp-null' => ' `{name}` TIMESTAMP DEFAULT NULL',
	'enum' => ' `{name}` ENUM({options})',
];

include_once '../packages/utils/Dot.php';
$env = Dot::handle();

$servername = $env->mysqlHost;
$username = $env->mysqlRootUser;
$password = $env->mysqlRootPassword;
$database = $env->mysqlDatabase;

// Create connection
$conn = new mysqli($servername, $username, $password);
// Check connection
if ($conn->connect_error) {
	exit('Connection failed: ' . $conn->connect_error);
}

// Check database
$query = 'SHOW DATABASES LIKE "' . $database . '"';
$resExists = $conn->query($query);

if ($resExists->num_rows == 1) {
	echo "Database already exists.\n";
} else {
	// Create database
	$sql = "CREATE DATABASE $database";
	if ($conn->query($sql) === true) {
		echo "Database created successfully.\n";
	}
}

$conn->query('use ' . $database);

// Create Tables...
$tablefiles = glob('./tables/*.yaml');
foreach ($tablefiles as $table) {
	$name = str_replace('.yaml', '', explode('/', $table)[2]);
	$file = file_get_contents($table);
	$parsed = (object) yaml_parse($file);
	$tables[$name] = $parsed;
	if (!$parsed->dependancy) {
		$order[] = $name;
	}
}

for ($repeat = count($tables); $repeat > 0; $repeat--) {
	foreach ($tables as $name => $table) {
		$find = $tables[$name]->dependancy;

		if ($find && !in_array($name, $order) && in_array($find, $order)) {
			$order[] = $name;
		}
	}
}

foreach ($order as $name) {
	$table = $tables[$name];

	$tableName = $table->tablename;

	$sql = "SHOW TABLES LIKE '$tableName'";
	$rep = $conn->query($sql);

	if ($rep->num_rows == 1) {
		echo "Table $tableName already exists.\n";
	} else {
		$columns = [];
		foreach ($table->columns as $name => $column) {
			$iterate = 0;
			if (str_ends_with($name, ')')) {
				$re = '/([\S]*?)\(([0-9]*?)\)/m';
				preg_match_all($re, $name, $matches, PREG_SET_ORDER, 0);
				$iterate = intval($matches[0][2]);
				$name = $matches[0][1];
			}

			if ($name === 'repeats') {
				$iterate = $column['amount'];
				for ($version = 1; $version <= $iterate; $version++) {
					foreach ($column['columns'] as $name => $template) {
						$numname = sprintf('%s_%s', $name, $version);
						$nameless = $tpl[$template];

						$create = str_replace('{name}', $numname, $nameless);
						$columns[] = $create;
					}
				}
			} else {
				$options = null;
				if (str_starts_with($column, 'enum')) {
					$options = str_replace(['enum[', ']', '/'], ['"', '"', '","'], $column);
					$column = 'enum';
				}

				$nameless = $tpl[$column];
				if ($options) {
					$nameless = str_replace('{options}', $options, $nameless);
				}

				if ($iterate) {
					for ($version = 1; $version <= $iterate; $version++) {
						$numname = sprintf('%s_%s', $name, $version);
						$create = str_replace('{name}', $numname, $nameless);
						$columns[] = $create;
					}
				} else {
					$create = str_replace('{name}', $name, $nameless);
					$columns[] = $create;
				}
			}
		}

		$sql = '';
		$sql .= "CREATE TABLE `$tableName` (\n";
		$sql .= join(",\n", $columns) . "\n";
		$sql .= ')';

		// echo $sql;

		if ($conn->query($sql) === true) {
			echo "Table $tableName created successfully\n";
		} else {
			echo 'Error creating table: ' . $conn->error . "\n";
		}
	}
}

$conn->close();
