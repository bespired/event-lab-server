<?php

use MyApp\Chat;
use Ratchet\Server\IoServer;

    require __DIR__ . '/vendor/autoload.php';

    echo "Server started\n";

    $server = IoServer::factory(
        new Chat(), 9001
    );

    $server->run();
