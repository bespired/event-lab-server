<?php

include_once "Hybi10.php";

class Socket
{

    public function send($data)
    {

        $host = 'docker.ratchet'; //where is the websocket server
        $port = 9001;

        $key = base64_encode(openssl_random_pseudo_bytes(16));

        $token = "eyJ0eXAi...0tLiF8DGI8";
        // How to get real token?

        $hd   = [];
        $hd[] = "GET / HTTP/1.1";
        $hd[] = "Host: $host/$token ";
        $hd[] = "Upgrade: websocket";
        $hd[] = "Connection: Upgrade";
        $hd[] = "Sec-WebSocket-Key: $key";
        $hd[] = "Sec-WebSocket-Version: 13";
        $hd[] = "Content-Length: " . strlen($data);

        $head = join("\r\n", $hd) . "\r\n\r\n";

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $hostname = "tls://$host:$port";
        $ttl      = ini_get("default_socket_timeout");
        $scc      = STREAM_CLIENT_CONNECT;

        $sock = stream_socket_client($hostname, $errno, $errstr, $ttl, $scc, $context);

        fwrite($sock, $head) or die('error:' . $errno . ':' . $errstr);

        $headers = fread($sock, 2000);

        fwrite($sock, Hybi10::hybi10Encode($data)) or die('error:' . $errno . ':' . $errstr);

        // $wsdata = fread($sock, 2000);
        // var_dump(Hybi10::hybi10Decode($wsdata));

        fclose($sock);

    }

}
