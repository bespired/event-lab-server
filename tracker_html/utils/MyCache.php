<?php

class MyCache
{
    private $servername;
    private $redis;

    public function __construct()
    {
        include_once 'Dot.php';
        $env = Dot::handle();

        if (! $env->redisHost) {
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

    // RPUSH tokens a02vk-prof-Dplhnl98:mail:pixel
    public function storeToken($token, $category, $action)
    {
        $value = sprintf('%s:%s:%s:%s', $token, $category, $action, time());
        $this->redis->rpush('tokens', $value);
    }

    public function topToken()
    {
        return $this->redis->rpop('tokens');
    }

    public function htStart()
    {
        $this->redis->setex('handling-tokens', 10, true);
    }

    public function htEnd()
    {
        $this->redis->del('handling-tokens');
    }

    public function isHT()
    {
        return $this->redis->exists('handling-tokens');
    }

}
