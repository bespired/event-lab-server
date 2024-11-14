<?php

// YES I KNOW

include_once '../packages/utils/globs.php';
include_once '../packages/utils/Handle.php';
include_once '../packages/utils/Dot.php';
$env = Dot::handle();

$servername = $env->mysqlHost;
$username   = $env->mysqlRootUser;
$password   = $env->mysqlRootPassword;
$database   = $env->mysqlDatabase;

$counts = [];

// Create connection
$conn = new mysqli($servername, $username, $password);
// Check connection
if ($conn->connect_error) {
    exit('Connection failed: ' . $conn->connect_error);
}

// Check database
$query     = 'SHOW DATABASES LIKE "' . $database . '"';
$resExists = $conn->query($query);

if ($resExists->num_rows !== 1) {
    echo "No such database exists.\n";
    exit;
}

$conn->query('use ' . $database);

$tablefiles = glob('./tables/*.yaml');
foreach ($tablefiles as $table) {
    $name          = str_replace('.yaml', '', explode('/', $table)[2]);
    $file          = file_get_contents($table);
    $parsed        = (object) yaml_parse($file);
    $tables[$name] = $parsed;
    $counts[$name] = 1;
    if (!$parsed->dependancy) {
        $order[] = $name;
    }
}

for ($repeat = count($tables); $repeat > 0; $repeat--) {
    foreach ($tables as $name => $table) {
        $find = $tables[$name]->dependancy;
        if (!in_array($name, $order) && in_array($find, $order)) {
            $order[] = $name;
        }
    }
}

$reserved = ['id', 'relations', 'amount'];
$tests    = ['project', 'cmne', 'name', 'email'];

foreach ($order as $name) {
    $table = $tables[$name];

    $tableName = $table->tablename;

    if (isset($table->seeds)) {
        $sql = "SHOW TABLES LIKE '$tableName'";
        $rep = $conn->query($sql);

        if ($rep->num_rows !== 1) {
            echo "No table $tableName exists.\n Did you create the DB?\n";
            exit;
        } else {
            $seedfolder = __DIR__ . "/seeds/$table->seeds/";
            $exists     = file_exists($seedfolder);

            if ($exists) {
                $seedfiles = glob($seedfolder . '*.yaml');

                foreach ($seedfiles as $seedname) {
                    $info = (object) pathinfo($seedname);
                    echo "Seed $info->filename for table $tableName\n";

                    $file  = file_get_contents($seedname);
                    $yamls = explode('---', $file);
                    array_shift($yamls);

                    // echo "==>";
                    // print_r($table);
                    // print_r($yamls);
                    // echo "\n";

                    foreach ($yamls as $index => $yaml) {
                        $relations    = null;
                        $table->index = $index + 1;
                        $parent       = insertOrUpdate($yaml, $table);

                        if ($relations) {
                            // DO THE RELATION DATA ...
                            foreach ($relations as $itable => $array) {
                                foreach ($array as $count => $values) {
                                    $thistable        = $tables[$itable];
                                    $thistable->index = $counts[$itable]++;
                                    $thisyaml         = yaml_emit($values);

                                    $child = insertOrUpdate($thisyaml, $thistable, $parent);
                                }
                            }
                        }
                    }
                }
            } else {
                echo "File $table->seeds.yaml not found for table $tableName.\n";
            }
        }
    }
}

$sql = 'UPDATE `accu_contacts` SET email = "joeri@bespired.nl" WHERE email LIKE "%@bespired.nl"';

$conn->query($sql);

$conn->close();

exit;

// ///

function tableWheres($table)
{
    global $tests;

    $columns = $table->columns;
    $wheres  = [];
    foreach ($tests as $test) {
        if (isset($columns[$test])) {
            $wheres[] = $test; // sprintf('`%s` = "%s"', $test, $columns->$test);
        }
    }

    return $wheres;
}

function insertOrUpdate($yaml, $table, $parent = null)
{
    global $conn, $reserved, $relations;

    $tableName = $table->tablename;

    $columns = [];
    $values  = [];
    $return  = [];
    $wheres  = tableWheres($table);

    $parsed    = (object) yaml_parse($yaml);
    $relations = isset($parsed->relations) ? $parsed->relations : null;

    // IS RECORD IN DB?
    $whats = [];
    foreach ($wheres as $where) {
        if (isset($parsed->$where)) {
            $whats[] = sprintf('`%s` = "%s"', $where, $parsed->$where);
            $display = $parsed->$where;
        }
    }
    $sql = '';
    $sql = "SELECT * FROM `$tableName` WHERE " . join(' AND ', $whats);

    $result = $conn->query($sql);

    // FOUND UPDATE
    if ($result->num_rows !== 0) {
        $updates = [];

        foreach ($parsed as $key => $value) {
            if (!in_array($key, $reserved)) {
                $value        = cast($value, $key, $table, $parsed, $parent);
                $updates[]    = sprintf('`%s` = %s', $key, $value);
                $return[$key] = $value;
            }
        }

        $sql = '';
        $sql .= sprintf("UPDATE `%s` \n", $tableName);
        $sql .= 'SET ' . join(', ', $updates) . " \n";
        $sql .= 'WHERE ' . join(' AND ', $whats);

        if ($conn->query($sql) === true) {
            echo "Data update successfully\n";
        } else {
            print_r($sql);

            echo 'Error update data: ' . $conn->error . "\n";
        }
    } else {
        //  NOT FOUND ... INSERT IT

        foreach ($parsed as $key => $value) {
            if (!in_array($key, $reserved)) {
                $columns[]    = "`$key`";
                $value        = cast($value, $key, $table, $parsed, $parent);
                $values[]     = $value;
                $return[$key] = $value;
            }
        }

        $sql = '';
        $sql .= sprintf("INSERT INTO `%s` (%s) \n", $tableName, join(',', $columns));
        $sql .= sprintf("VALUES (%s) \n", join(',', $values));

        // print_r($sql);

        if ($conn->query($sql) === true) {
            echo "Data insert successfully\n";
        } else {
            print_r($sql);

            echo 'Error creating data: ' . $conn->error . "\n";
        }
    }

    return $return;
}

function cast($value, $key, $table, $parsed, $parent)
{
    global $env, $seeded;

    if ($value && str_starts_with($value, '(') && strpos($value, ':') > 0) {
        $where  = explode(':', trim($value, '()'))[0];
        $search = explode(':', trim($value, '()'))[1];
        $value  = '(find)';
    }

    switch ($value) {
    case 'null':
    case null:
        return 'NULL';

    case '(APP_PASSWORD)':
        return '"' . password_hash($env->appPassword, PASSWORD_BCRYPT) . '"';

    case '(find)':
        $found = isset($seeded[$search]) ? $seeded[$search] : "$where:$search";

        return "\"$found\"";

    case '(parent)':
        return $parent['handle'];

    case '(now)':
        return 'NOW()';

    case '(now+month)':
        $date = new DateTime();
        $date->modify('+1 month');

        return '"' . $date->format('Y-m-d H:i:s') . '"';

    case '(auto)':
        $handle = Handle::create($parsed, $table);
        // print_r($table->tablename);
        // echo "-> ";
        // print_r($parsed);
        // echo "\n";

        if ($table->tablename === 'accu_contacts') {
            $seeded[$parsed->email] = $handle;
        }
        if ($table->tablename === 'proj_accesses') {
            $seeded[$parsed->cmne] = $handle;
        }

        return '"' . $handle . '"';

    default:
        $type = isset($table->columns[$key]) ? $table->columns[$key] : 'unkown';

        switch ($type) {
        case 'int':
            return $value;

        case 'boolean':
            return $value ? 1 : 0;

        case 'data':
            return '"' . addslashes($value) . '"';

        case 'base1':
        case 'base26':
        case 'mnemonic':
            return '"' . addslashes($value) . '"';

        default:
            return '"' . addslashes($value) . '"';
        }
    }
}

// INSERT INTO table_name (column1, column2, column3, ...)
// VALUES (value1, value2, value3, ...);

// UPDATE table_name
// SET column1 = value1, column2 = value2, ...
// WHERE condition;

//
//   \0     An ASCII NUL (0x00) character.
//   \'     A single quote (“'”) character.
//   \"     A double quote (“"”) character.
//   \b     A backspace character.
//   \n     A newline (linefeed) character.
//   \r     A carriage return character.
//   \t     A tab character.
//   \Z     ASCII 26 (Control-Z). See note following the table.
//   \\     A backslash (“\”) character.
//   \%     A “%” character. See note following the table.
//   \_     A “_” character. See note following the table.
