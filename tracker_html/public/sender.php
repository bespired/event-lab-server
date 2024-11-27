<?php

include_once "../utils/Socket.php";
(new Socket())->send(json_encode(['test' => 'true', 'time' => time()]));
