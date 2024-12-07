<?php

// YES I KNOW

include_once '../packages/utils/globs.php';
include_once '../packages/utils/Handle.php';
include_once '../packages/utils/MyDB.php';

// VisitDB creates loads of visitor in DB

include_once 'visits/cities.php';
include_once 'visits/streets.php';
include_once 'visits/names.php';

$tablefiles = glob('./visits/*.yaml');
foreach ($tablefiles as $table) {
    $name   = str_replace('.yaml', '', explode('/', $table)[2]);
    $file   = file_get_contents($table);
    $parsed = (object) yaml_parse($file);

    print_r($parsed);
}
