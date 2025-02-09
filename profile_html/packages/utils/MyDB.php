<?php

class MyDB
{

    private $servername;
    private $username;
    private $password;
    private $database;
    private $conn;

    public function __construct()
    {

        include_once "Dot.php";
        $env = Dot::handle();

        $this->servername = $env->mysqlHost;
        $this->username   = $env->mysqlRootUser;
        $this->password   = $env->mysqlRootPassword;
        $this->database   = $env->mysqlDatabase;

    }

    public function connect()
    {
        if ($this->conn) {
            return;
        }

        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->database);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function close()
    {
        if (! $this->conn) {
            return;
        }

        $this->conn->close();
    }

    public function insert($tableName, $slots)
    {

        foreach ($slots as $key => $value) {
            $keys[] = "`$key`";

            $quoted   = is_null($value) ? '' : sprintf('"%s"', addslashes($value));
            $value    = is_bool($value) ? ($value ? 1 : 0) : $value;
            $values[] = is_null($value) ? 'NULL' : (is_string($value) ? $quoted : $value);
        }

        $columns = join(', ', $keys);
        $inserts = join(', ', $values);

        $sql = '';
        $sql .= sprintf("INSERT INTO `%s` (%s) \n", $tableName, $columns);
        $sql .= sprintf('VALUES (%s) ', $inserts);

        $this->connect();
        $result = $this->conn->query($sql);

        return $result;

    }

    public function truncate($tableName)
    {
        $this->connect();
        $this->conn->query("TRUNCATE TABLE `$tableName`");
    }

    public function update($tableName, $slots, $whereis)
    {

        $updates = [];
        $wheres  = [];

        // simple CHANGE ...
        foreach ($slots as $key => $value) {
            $updates[] = sprintf('`%s` = "%s"', $key, $value);
        }
        $update = join(', ', $updates);

        // simple ANDS ...
        foreach ($whereis as $key => $value) {
            $wheres[] = sprintf('`%s` = "%s"', $key, $value);
        }
        $where = '(' . join('","', $wheres) . ')';

        $sql = "";
        $sql .= sprintf("UPDATE `%s` \n", $tableName);
        $sql .= sprintf("SET %s \n", $update);
        $sql .= sprintf("WHERE %s \n", $where);

        $this->connect();
        $result = $this->conn->query($sql);

        return $result;

    }

    public function selectKey($sql, $key)
    {
        $reads = $this->select($sql);
        foreach ($reads as $row) {
            $rows[$row[$key]]        = $row;
            $rows[$row[$key]]['key'] = explode('--', $row['name'])[1];
        }
        return $rows;
    }

    public function select($sql)
    {
        $this->connect();

        $result = $this->conn->query($sql);
        if (! $result->num_rows) {
            return null;
        }

        while ($row = $result->fetch_assoc()) {
            $low    = array_change_key_case($row);
            $rows[] = $low;
        }

        return $rows;

    }

    public function renamed($sql, $attr)
    {

        $rows = $this->first($sql);

        foreach ($rows as $key => $row) {
            if (str_starts_with($key, 'attr_')) {
                if (isset($attr[$key])) {
                    $named = $attr[$key]['name'];
                    $named = explode("--", $named)[1];

                    $rows[$named] = $row;
                }
                unset($rows[$key]);

            }

        }
        return $rows;
    }

    public function first($sql)
    {
        $result = $this->select($sql);
        if (! $result) {
            return null;
        }

        return $result[0];
    }

}
