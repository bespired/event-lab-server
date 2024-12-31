<?php

$query = @$_SERVER['QUERY_STRING'];

if ($query === 'glyphs') {
    $list  = [];
    $files = glob(__DIR__ . '/glyph/*.svg');
    foreach ($files ?? [] as $file) {
        $route  = explode("/", $file);
        $name   = end($route);
        $list[] = str_replace('.svg', '', $name);
    }
    echo json_encode($list);
}
