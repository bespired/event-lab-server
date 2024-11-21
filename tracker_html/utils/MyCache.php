<?php

class MyCache
{
    private $servername;
    private $redis;

    public function __construct()
    {
        include_once 'Dot.php';
        $env = Dot::handle();

        if (!$env->redisHost) {
            echo "missing redisHost in env\n";
            exit;
        }

        $this->redis = new Redis();
        $this->redis->connect($env->redisHost, $env->redisPort);
        $this->redis->rawCommand('auth', 'default', $env->redisRootPassword);
    }

    public function close()
    {
        $this->redis->close();
    }

    public function labWrite($token, $payload)
    {
        $quotedKey = addslashes($token);
        $value     = $payload ? json_encode($payload) : null;
        $this->redis->setex($quotedKey, STL, $value);
    }

    public function labRead($token)
    {
        $quotedKey = addslashes($token);
        $value     = $this->redis->get($quotedKey);

        return $value ? json_decode($value) : null;
    }

    public function labDelete($token)
    {
        $quotedKey = addslashes($token);
        $this->redis->del($quotedKey);
    }

    public function isLabStored($token)
    {
        $quotedKey = addslashes($token);

        return $this->redis->exists($quotedKey);
    }
}
