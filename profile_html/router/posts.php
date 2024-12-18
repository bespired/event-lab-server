<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    include_once "Router.php";

    $router = new Router();

    switch ($router->package) {
        case 'login':
            include_once __DIR__ . "/../packages/login/Login.php";
            (new Login())->handle($router);
            break;

        case 'create':
            $router->protected();
            include_once __DIR__ . "/../packages/create/Create.php";
            Create::handle($router);
            break;

        case 'user':
            $router->protected();
            include_once __DIR__ . "/../packages/user/User.php";
            (new User())->handle($router);
            break;

        case 'lab':
            $router->protected();
            include_once __DIR__ . "/../packages/lab/Lab.php";
            (new Lab())->handle($router);
            break;

        case 'profiles':
            $router->protected();

            switch ($router->action) {
                case 'list':
                    include_once __DIR__ . '/../packages/profile/Profile.php';
                    (new Profile($router->projectChar))->list($router);
                    break;
            }

            break;

    }

    exit(0);

}
