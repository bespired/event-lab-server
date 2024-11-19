<?php

// [REQUEST_URI] => /--/tracker/{token}/pixel.gif
$token = explode('/', $_SERVER['REQUEST_URI'])[3];

// Put token in REDIS for handling as mail open

// Once invented to be smallest/fastest response ever.
// Gif transparent pixel of 43 bytes

$pixel[] = '47:49:46:38:39:61:01:00:01:00:80:00:00:FF:FF:FF:';
$pixel[] = '00:00:00:21:F9:04:01:00:00:00:00:2C:00:00:00:00:';
$pixel[] = '01:00:01:00:00:02:02:44:01:00:3B';

$hexes   = explode(':', join('', $pixel));
$bytes   = array_map(fn ($hex) => chr(hexdec($hex)), $hexes);

header('Content-Type: image/gif');
echo join('', $bytes);
