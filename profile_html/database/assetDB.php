<?php

// YES I KNOW

include_once '../packages/utils/globs.php';
include_once '../packages/utils/Handle.php';
include_once '../packages/utils/MyDB.php';
include_once '../packages/utils/ExtoMime.php';

$db = new MyDB();

$sql   = 'SELECT * FROM `accu_contacts` WHERE `role` = "bespired"';
$found = $db->first($sql);

if (is_null($found)) {
    echo "Need an owner in DB. \n";
    exit;
}

$contact  = $found['handle'];
$count    = 0;
$time     = time();
$datecode = sprintf('%03s%02s', date('z', $time), substr(date('Y', $time), 2, 2));

// -- ASSETS

$root = realpath(__DIR__ . '/../../public_html/public/assets');
if (! $root) {
    echo "Cannot find `public_html/public/assets/`. \n";
    exit;
}

foreach (types() as $typename => $settings) {

    $folder           = $settings['folder'];
    $settings['root'] = $root;

    for ($example = $settings['count']; $example > 0; $example--) {
        $count++;

        list($name, $extention) = createAsset($typename, $settings, $example);
        $filename               = sprintf('%s/%s/%s.%s', $root, $folder, $name, $extention);

        $payload = [];
        $cmne    = $settings['cmne'];
        $mime    = $exttomime[".$extention"];
        $asset   = Handle::make($count, $cmne, 'asset');

        $url = sprintf('/asset/%s/%s.%s', $folder, $name, $extention);
        $url = str_replace('/dated/', "/$datecode/", $url);

        $payload['handle']   = $asset;
        $payload['owner']    = $contact;
        $payload['project']  = 'a';
        $payload['cmne']     = $cmne;
        $payload['type']     = $typename;
        $payload['mimetype'] = $mime;
        $payload['version']  = 1;
        $payload['url']      = $url;
        $payload['name']     = sprintf('%s--%s', $name, 1);
        $payload['label']    = ucfirst($name);
        $payload['tags']     = sprintf('["%s", "%s"]', $typename, $extention);

        $db->insert('sys_assets', $payload);

    }
}

echo "$count assets made.\n";

// -- THANK YOU

$db->close();

exit;

function createAsset($typename, $settings, $count)
{

    // find -L /Users/joeri/Library/Fonts -name "*.ttf"

    $root   = $settings['root'];
    $folder = $settings['folder'];

    if ($folder === 'dated') {
        $time   = time();
        $folder = sprintf('%03s%02s', date('z', $time), substr(date('Y', $time), 2, 2));
    }

    $dirname = sprintf('/%s/%s', $root, $folder);
    if (! file_exists($dirname)) {
        @mkdir($dirname);
    }

    $name = $typename . '-' . $count;

    switch ($typename) {

        case "illustration":
        case "picture":
        case "email-image":
        case "image":
            $extention = 'png';
            $name      = $typename . '-' . $count;

            $png_image = imagecreate(640, 400);
            imagecolorallocate($png_image, 15, 142, 210);

            $filename = sprintf('%s/%s/%s.%s', $root, $folder, $name, $extention);
            imagepng($png_image, $filename);
            imagedestroy($png_image);
            break;
        case "icon":
        case "logo":
        case "link":
            $extention = 'png';
            $name      = $typename . '-' . $count;
            $x         = $typename !== 'link' ? 45 * $count : 135;
            $y         = $typename !== 'link' ? 25 * $count : 90;

            $x = $typename === 'icon' ? 150 : $x;
            $y = $typename === 'icon' ? 150 : $y;

            $size = $x < 50 ? 10 : 12;

            $r = $typename === 'icon' ? rand(10, 255) : rand(15, 120);
            $g = rand(140, 240);
            $b = rand(200, 255);

            $png_image = imagecreate($x, $y);
            imagecolorallocate($png_image, $r, $g, $b);
            $white = imagecolorallocate($png_image, 255, 255, 255);

            $font_path = '/Users/joeri/Library/Fonts/Roboto-Black.ttf';

            $text  = "$x x $y";
            $space = imagettfbbox($size, 0, $font_path, $text);

            $width  = abs($space[4] - $space[0]);
            $height = abs($space[5] - $space[1]);
            $mx     = (int) ($x / 2 - $width / 2);
            $my     = (int) ($y / 2 + $height / 2);
            $my     = (int) ($y / 2);

            imagettftext($png_image, $size, 0, $mx, $my, $white, $font_path, $text);
            imagettftext($png_image, $size, 0, $mx, $my + 2 + $size, $white, $font_path, $typename);

            $filename = sprintf('%s/%s/%s.%s', $root, $folder, $name, $extention);
            imagepng($png_image, $filename);
            imagedestroy($png_image);

            break;
        case "document":
            $extention = 'pdf';
            break;
        case "font-file":
            $extention = 'ttf';
            break;
        case "script-file":
            $extention = 'js';
            break;
        case "style-file":
            $extention = 'css';
            break;
    }

    // $name      = 'file' . $count;
    // $extention = 'gif';

    return [$name, $extention];
}

#illustrations  article-illustration <=  in dated folder
#pictures       page-image           <=  in dated folder
#email          email-illustration   <=  in dated folder
#images         website-image        <=  in image folder
#icons          website-icon         <=  in icon  folder
#logos          website-logo         <=  in logo  folder
#links          link-image           <=  in link  folder
#documents      download-document    <=  in file  folder
#font-file      font-file            <=  in font  folder
#font-glyph     font-glyph           <=  in glyph folder
#style-file     style-file           <=  in style folder
#script-file    script-file          <=  in script folder
#private        private-file         <=  route to file in private folder

function types()
{
    return [
        "illustration" => ["cmne" => 'ASIL', "count" => 10, "folder" => 'dated'],
        "picture"      => ["cmne" => 'ASPC', "count" => 3, "folder" => 'dated'],
        "email-image"  => ["cmne" => 'ASEI', "count" => 5, "folder" => 'dated'],
        "image"        => ["cmne" => 'ASIM', "count" => 4, "folder" => 'image'],
        "icon"         => ["cmne" => 'ASIC', "count" => 12, "folder" => 'icon'],
        "logo"         => ["cmne" => 'ASLG', "count" => 3, "folder" => 'logo'],
        "link"         => ["cmne" => 'ASLK', "count" => 8, "folder" => 'link'],
        "document"     => ["cmne" => 'ASDC', "count" => 1, "folder" => 'file'],
        "font-file"    => ["cmne" => 'ASFF', "count" => 1, "folder" => 'font'],
        // "font-glyph"   => ["cmne" => 'ASFG', "count" => 150, "folder" => 'glyph'],
        "script-file"  => ["cmne" => 'ASJS', "count" => 0, "folder" => 'script'],
        "style-file"   => ["cmne" => 'ASCS', "count" => 0, "folder" => 'style'],
    ];
}
