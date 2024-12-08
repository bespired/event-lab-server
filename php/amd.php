<?php

$filename = "docker-compose.yml";

$findrep = [
    'image: arm64v8/mysql:latest' => 'image: mysql:latest',
    'platform: linux/arm64'       => '# platform: linux/arm64',
];

if (file_exists($filename)) {
    $content = file_get_contents($filename);

    foreach ($findrep as $find => $replace) {
        $content = str_replace($find, $replace, $content);

    }
    file_put_contents($filename, $content);
    echo "Swapped arm64v8 into amd in file $filename \n";
} else {
    echo "Cannot find $filename \n";
}
