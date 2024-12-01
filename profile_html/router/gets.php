<?php

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    include_once 'Router.php';

    $router = new Router();

    switch ($router->package) {
        case 'root':
            include_once '../packages/root/Root.php';
            Root::handle($router);
            break;

        case 'card':
            include_once '../packages/card/Card.php';
            Card::handle($router);
            break;
    }

    include_once '../router/html.php';
    exit(0);
}
