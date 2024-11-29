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

    public function subscribe($callback)
    {
        return $this->redis->subscribe(['channel11'], $callback);
    }

    public function tellChannel11($message)
    {
        $this->redis->publish('channel11', $message);
    }

    public function close()
    {
        $this->redis->close();
    }

    public function llen($name)
    {
        return $this->redis->llen($name);
    }

    // -- START TRACK HELPERS

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

    // -- PIXEL HELPERS

    public function storeToken($token, $category, $action)
    {
        $value = sprintf('%s::%s::%s::%s', $token, $category, $action, time());
        $this->redis->rpush('pop-tokens', $value);
    }

    public function topToken()
    {
        return $this->redis->rpop('pop-tokens');
    }

    // -- LANDING HELPERS

    public function storeVisit($visitor, $session, $data)
    {
        $value = sprintf('%s::%s::%s::%s', $visitor, $session, time(), $data);
        $this->redis->rpush('pop-visits', $value);
    }

    public function topVisit()
    {
        return $this->redis->rpop('pop-visits');
    }

    // -- GEO HELPERS

    public function storeGeo($profile, $realip)
    {
        $value = sprintf('%s||%s||%s', $profile, $realip, time());
        $this->redis->rpush('pop-geos', $value);
    }

    public function topGeo()
    {
        return $this->redis->rpop('pop-geos');
    }

    // -- LOG HELPERS

    public function storeLog($message)
    {
        $date  = date("Y-m-d H:i:s");
        $value = sprintf('%s: %s', $date, $message);
        $this->redis->rpush('pop-logs', $value);
    }

    // -- HANDLING HELPERS

    public function htStart($category)
    {
        $this->redis->setex('handling-' . $category, 10, true);
    }

    public function htEnd($category)
    {
        $this->redis->del('handling-' . $category);
    }

    public function isHT($category)
    {
        return $this->redis->exists('handling-' . $category);
    }
}

// echo 'AUTH redis\nping' | redis-cli
// echo -e 'AUTH PASSWORD\nkeys *' | redis-cli
// echo -e 'AUTH aYVX7EwVmmxKPCDmwMtyKVge8oLd2t82\nCONFIG SET requirepass ""' | redis-cli
