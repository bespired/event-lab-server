<?php

$servername = "bespired.com";

// change `eventlab.com` to current servername

$filenames = ["docker-compose.yml"];

foreach ($filenames as $filename) {
    $content = file_get_contents($filename);
    $content = str_replace('eventlab.com', $servername, $content);
    file_put_contents($filename, $content);
}
