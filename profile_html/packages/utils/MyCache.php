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

    public function test()
    {
        $this->redis->setex('hello', 60, 'Hello World');
        echo $this->redis->get('hello');
    }
}
