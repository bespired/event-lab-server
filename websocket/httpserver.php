<?php

use MyApp\Chat;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

require __DIR__ . '/vendor/autoload.php';

echo "Server started\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    9002
);

$server->run();

// $loop = React\EventLoop\Factory::create();

// // $webSock = new React\Socket\Server('0.0.0.0:9001', $loop);
// $webSock = new React\Socket\Server('0.0.0.0:9002', $loop);

// $webSock = new React\Socket\SecureServer($webSock, $loop, [
//     'local_cert' => '/certs/eventlab.com.crt', // path to your cert
//     'local_pk' => '/certs/eventlab.com.key',   // path to your server private key
//     'allow_self_signed' => true,               // Allow self signed certs (should be false in production)
//     'verify_peer' => false,
// ]);

// $webServer = new IoServer(
//     new HttpServer(
//         new WsServer(
//             new Chat()
//         )
//     ),
//     $webSock,
// );

// echo 'Socket server runing at: ' . $webSock->getAddress() . "\n";

// $loop->run();
