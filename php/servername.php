<?php

$servername = "bespired";
// change `eventlab.com` to current servername

$filenames = [
    "docker-compose.yml",
    "profile_html/docker.env",
    "tracker_html/docker.env",
    "public_html/docker.env",
    "public_html/public/.htaccess",
    "profile_html/public/.htaccess",
    "profile_html/database/seeds/projects/projects.yaml",
];

foreach ($filenames as $filename) {
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        $content = str_replace('eventlab', $servername, $content);
        file_put_contents($filename, $content);
        echo "Swapped eventlab into $servername in file $filename \n";
    } else {
        echo "Cannot find $filename \n";
    }
}