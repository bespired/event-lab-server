<?php

class MyDB
{
    public $env;
    private $servername;
    private $username;
    private $password;
    private $database;
    private $conn;

    public function __construct()
    {
        include_once 'Dot.php';
        $env = Dot::handle();

        $this->servername = $env->mysqlHost;
        $this->username   = $env->mysqlRootUser;
        $this->password   = $env->mysqlRootPassword;
        $this->database   = $env->mysqlDatabase;
        $this->env        = $env;
    }

    public function connect()
    {
        if ($this->conn) {
            return;
        }

        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->database);

        if ($this->conn->connect_error) {
            exit('Connection failed: ' . $this->conn->connect_error);
        }
    }

    public function close()
    {
        if (! $this->conn) {
            return;
        }

        $this->conn->close();
    }

    public function findToken($token)
    {
        $sql = '';
        $sql .= 'SELECT `handle`, `profile`, `contact`, `project`, `pointer` FROM `track_tokens` ';
        $sql .= 'WHERE CONCAT(';
        $sql .= '  COALESCE(`token_1`, ""), "::", COALESCE(`token_2`, ""), "::", COALESCE(`token_3`, ""), "::", ';
        $sql .= '  COALESCE(`token_4`, ""), "::", COALESCE(`token_5`, ""), "::", COALESCE(`token_6`, ""), "::", ';
        $sql .= '  COALESCE(`token_7`, ""), "::", COALESCE(`token_8`, ""), "::", COALESCE(`token_9`, ""), "::", ';
        $sql .= '  COALESCE(`token_10`, ""), "::", COALESCE(`token_11`, ""), "::", COALESCE(`token_12`, ""), "::", ';
        $sql .= '  COALESCE(`token_13`, ""), "::", COALESCE(`token_14`, ""), "::", COALESCE(`token_15`, ""), "::", ';
        $sql .= '  COALESCE(`token_16`, ""), "::", COALESCE(`token_17`, ""), "::", COALESCE(`token_18`, ""), "::", ';
        $sql .= '  COALESCE(`token_19`, ""), "::", COALESCE(`token_20`, ""), "::", COALESCE(`token_21`, ""), "::", ';
        $sql .= '  COALESCE(`token_22`, ""), "::", COALESCE(`token_23`, ""), "::", COALESCE(`token_24`, ""), "::", ';
        $sql .= '  COALESCE(`token_25`, ""), "::", COALESCE(`token_26`, ""), "::", COALESCE(`token_27`, ""), "::", ';
        $sql .= '  COALESCE(`token_28`, ""), "::", COALESCE(`token_29`, ""), "::", COALESCE(`token_30`, "") ';
        $sql .= ') LIKE "%' . $token . '%"';

        $result = $this->select($sql);
        if (! $result) {
            return null;
        }

        return $result[0];

    }

    public function findGeoLocation($realip, $profile)
    {

        $project = substr($profile, 0, 1);

        $sql = '';
        $sql .= 'SELECT `handle`, `profile` FROM `accu_geolocation` ';
        $sql .= 'WHERE `project` = "' . $project . '" ';
        $sql .= 'AND CONCAT(';
        $sql .= '  COALESCE(`real_ip_1`, ""), "||", ';
        $sql .= '  COALESCE(`real_ip_2`, ""), "||", ';
        $sql .= '  COALESCE(`real_ip_3`, ""), "||", ';
        $sql .= '  COALESCE(`real_ip_4`, ""), "||", ';
        $sql .= '  COALESCE(`real_ip_5`, ""), "||" ';
        $sql .= ') LIKE "%' . $realip . '%"';

        $result = $this->select($sql);
        if (! $result) {
            return null;
        }

        return $result[0];

    }

    public function increment($tableName, $column, $where, $amount = 1)
    {
        $sql = sprintf('UPDATE `%s` SET `%s` = `%s` + %s WHERE `handle` = "%s"',
            $tableName, $column, $column, $amount, $where);

        // file_put_contents(__DIR__ . '/../public/tmp.log',
        // sprintf("%s %s\n", date('Y.m.d H:i:s'), $sql), FILE_APPEND);

        $this->connect();
        $result = $this->conn->query($sql);

        return $result;
    }

    public function insert($tableName, $slots)
    {

        foreach ($slots as $key => $value) {
            $keys[] = "`$key`";

            $quoted   = is_null($value) ? '' : sprintf('"%s"', addslashes($value));
            $values[] = is_null($value) ? 'NULL' : (is_string($value) ? $quoted : $value);
        }

        $columns = join(', ', $keys);
        $inserts = join(', ', $values);

        $sql = '';
        $sql .= sprintf("INSERT INTO `%s` (%s) \n", $tableName, $columns);
        $sql .= sprintf('VALUES (%s) ', $inserts);

        // file_put_contents(__DIR__ . '/../public/tmp.log',
        // sprintf("%s %s\n", date('Y.m.d H:i:s'), $sql), FILE_APPEND);

        $this->connect();
        $result = $this->conn->query($sql);

        return $result;
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

        $sql = '';
        $sql .= sprintf("UPDATE `%s` \n", $tableName);
        $sql .= sprintf("SET %s \n", $update);
        $sql .= sprintf("WHERE %s \n", $where);

        $this->connect();
        $result = $this->conn->query($sql);

        return $result;
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

    public function first($sql)
    {
        $result = $this->select($sql);
        if (! $result) {
            return null;
        }

        return $result[0];
    }

    public function count($sql)
    {
        $result = $this->select($sql);
        if (! $result) {
            return null;
        }

        return intval(reset($result[0]));
    }

}
