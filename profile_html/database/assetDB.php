<?php

// YES I KNOW

include_once '../packages/utils/globs.php';
include_once '../packages/utils/Handle.php';
include_once '../packages/utils/MyDB.php';
include_once '../packages/utils/ExtoMime.php';
include_once '../packages/utils/Woff.php';
include_once '../packages/utils/PDF2Text.php';

// use Woff;
// @see         https://github.com/teicee/php-woff-converter
// @author      Grégory Marigot (téïcée) <gmarigot@teicee.com> (@proxyconcept)

$db = new MyDB();

$sql   = 'SELECT * FROM `accu_contacts` WHERE `role` = "bespired"';
$found = $db->first($sql);

if (is_null($found)) {
    echo "Need an owner in DB. \n";
    exit;
}

removeOldInstall($db);

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

        $file = sprintf('/%s/%s.%s', $folder, $name, $extention);
        $file = str_replace('/dated/', "/$datecode/", $file);

        $url = sprintf('/assets/%s/%s.%s', $folder, $name, $extention);
        $url = str_replace('/dated/', "/$datecode/", $url);

        $payload['handle']     = $asset;
        $payload['owner']      = $contact;
        $payload['project']    = 'a';
        $payload['cmne']       = $cmne;
        $payload['type']       = $typename;
        $payload['mimetype']   = $mime;
        $payload['version']    = 1;
        $payload['dimensions'] = filedimensions($root . $file);
        $payload['size']       = human_filesize(filesize($root . $file));
        $payload['url']        = $url;
        $payload['name']       = sprintf('%s--%s', $name, 1);
        $payload['label']      = ucfirst(str_replace('-', ' ', $name));
        $payload['tags']       = sprintf('["%s", "%s"]', $typename, $extention);

        $db->insert('sys_assets', $payload);

    }
}

echo "$count assets made.\n";

// -- THANK YOU

$db->close();

exit;

function filedimensions($filename)
{
    $data = @getimagesize($filename);

    if ($data) {
        return $data[0] . 'px, ' . $data[1] . 'px';
    }

    if (str_ends_with($filename, '.pdf')) {
        $stream = new SplFileObject($filename);

        $result = false;

        $re  = '/\/MediaBox \[([\s\S]*)\]/m';
        $str = '/MediaBox [0 0 612 792]';

        while (! $stream->eof()) {
            $read = $stream->fgets();

            preg_match_all($re, $read, $matches, PREG_SET_ORDER, 0);

            if (count($matches)) {
                $values    = explode(' ', $matches[0][1]);
                $widthUSU  = intval($values[2]) - intval($values[0]);
                $heightUSU = intval($values[3]) - intval($values[1]);

                $widthMM  = round($widthUSU * 0.35306);
                $heightMM = round($heightUSU * 0.35306);

                return sprintf('%smm, %smm', $widthMM, $heightMM);
                break;
            }

        }

        // /MediaBox [0 0 612 792] = Letter
        // User space unit  1/72 inch = 0.35306 mm
        // A4 = 210 x 297 mm    8.3 x 11.7 in
        // 216.07272 x 279.62352
        $stream = null;

    }
    return '';
}

function human_filesize($bytes)
{
    $factor = floor((strlen($bytes) - 1) / 3);
    if ($factor > 0) {
        $sz = 'KMGT';
    }

    return sprintf("%.0f ", $bytes / pow(1024, $factor)) . @$sz[$factor - 1] . 'B';
}

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
            $extention = rand(0, 1) < 0.5 ? 'png' : 'jpg';
            $name      = $typename . '-' . $count;
            echo "creating $name \n";

            $x = round(rand(640, 800));
            $y = round(rand(400, $x - 20));

            thumb($typename, $root, $folder, $name, $extention, $x, $y);
            break;

        case "icon":
        case "logo":
        case "link":
            $extention = 'png';
            $name      = $typename . '-' . $count;

            $x = $typename !== 'link' ? 45 * $count : 135;
            $y = $typename !== 'link' ? 25 * $count : 90;
            $x = $typename === 'icon' ? 150 : $x;
            $y = $typename === 'icon' ? 150 : $y;

            echo "creating $name \n";

            thumb($typename, $root, $folder, $name, $extention, $x, $y);

            break;
        case "document":
            $extention = 'pdf';
            echo "creating $name \n";

            $src = 'https://www.learningcontainer.com/wp-content/uploads/2019/09/sample-pdf-file.pdf';
            echo "loading from $src \n";

            $pdffile  = file_get_contents($src);
            $filename = sprintf('%s/%s/%s.%s', $root, $folder, $name, $extention);
            file_put_contents($filename, $pdffile);
            break;

        case "font-file":
            $extention = 'woff';
            $name      = 'abel-latin-400-normal';

            echo "creating $name \n";
            $src = 'https://fonts.bunny.net/abel/files/abel-latin-400-normal.woff';
            echo "loading from $src \n";

            $font     = file_get_contents($src);
            $filename = sprintf('%s/%s/%s.%s', $root, $folder, $name, $extention);
            file_put_contents($filename, $font);

            // // https://github.com/teicee/php-woff-converter/blob/main/README.md
            Woff::toTTF($filename);

            thumb('font-file', $root, $folder, $name, 'png', 640, 480, $filename);

            break;

        case "font-glyph":

            $extention = 'zip';
            $name      = 'iconfont';

            echo "creating $name \n";
            $src = 'http://joeri67.nl/glyphs.zip';
            echo "loading from $src \n";

            $font     = file_get_contents($src);
            $filename = sprintf('%s/%s/%s.%s', $root, $folder, $name, $extention);
            $pathname = sprintf('%s/%s/', $root, $folder);
            file_put_contents($filename, $font);

            // unzip -j /path/to/file.zip -d other_folder

            $cmd = "unzip -j \"$filename\" -d \"$pathname\"";
            shell_exec($cmd);

            $dotfiles = glob($pathname . '._*');
            foreach ($dotfiles as $dotfile) {
                @unlink($dotfile);
            }

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

function thumb($typename, $root, $folder, $name, $extention, $x, $y, $fontfile = null)
{

    $size = $x < 50 ? 10 : 12;
    $size = $x > 500 ? 18 : $size;

    $r = $typename === 'icon' ? rand(10, 255) : rand(15, 120);
    $g = rand(140, 240);
    $b = rand(200, 255);
    $t = 64;

    $text      = "$x x $y";
    $font_path = $root . '/font/' . 'abel-latin-400-normal.ttf';

    if ($typename === 'font-file') {
        $r         = 153;
        $g         = 180;
        $b         = 76;
        $text      = "ABCDEFGHIJKLM";
        $font_path = $fontfile;
        $t         = 127;
    }

    $png_image = imagecreatetruecolor($x, $y);
    imagesavealpha($png_image, true);

    imagecolorallocate($png_image, $r, $g, $b);
    $bgcolor = imagecolorallocatealpha($png_image, $r, $g, $b, $t);
    imagefill($png_image, 0, 0, $bgcolor);

    $white = imagecolorallocate($png_image, 255, 255, 255);
    $black = imagecolorallocate($png_image, 0, 0, 0);

    $space = imagettfbbox($size, 0, $font_path, $text);

    $width  = abs($space[4] - $space[0]);
    $height = abs($space[5] - $space[1]);
    $mx     = (int) ($x / 2 - $width / 2);
    $my     = (int) ($y / 2 + $height / 2);
    $my     = (int) ($y / 2);

    if ($typename === 'font-file') {
        $size  = 48;
        $texts = [
            "ABCDEFGHIJKLM",
            "NOPQRSTUVWXYZ",
            "abcdefghijklm",
            "nopqrstuvwxyz",
            "1234567890",
        ];
        $neg = -2.5 * $size;
        foreach ($texts as $idx => $line) {
            $space  = imagettfbbox($size, 0, $font_path, $line);
            $width  = abs($space[4] - $space[0]);
            $width2 = round($width / 2);
            $offs   = $neg + ($size + 8) * $idx;
            imagettftext($png_image, $size, 0, 320 - $width2, $my + $offs, $black, $font_path, $line);
        }

    } else {
        imagettftext($png_image, $size, 0, $mx, $my, $white, $font_path, $text);
        imagettftext($png_image, $size, 0, $mx, $my + 2 + $size, $white, $font_path, $typename);
    }
    $filename = sprintf('%s/%s/%s.%s', $root, $folder, $name, $extention);
    if ($extention === 'png') {imagepng($png_image, $filename);}
    if ($extention === 'jpg') {imagejpeg($png_image, $filename, 95);}

    imagedestroy($png_image);

}

function types()
{
    return [
        "font-file"    => ["cmne" => 'ASFF', "count" => 1, "folder" => 'font'],
        "document"     => ["cmne" => 'ASDC', "count" => 1, "folder" => 'file'],
        "font-glyph"   => ["cmne" => 'ASFG', "count" => 1, "folder" => 'glyph'],
        "illustration" => ["cmne" => 'ASIL', "count" => 10, "folder" => 'dated'],
        "picture"      => ["cmne" => 'ASPC', "count" => 3, "folder" => 'dated'],
        "email-image"  => ["cmne" => 'ASEI', "count" => 5, "folder" => 'dated'],
        "image"        => ["cmne" => 'ASIM', "count" => 4, "folder" => 'image'],
        "icon"         => ["cmne" => 'ASIC', "count" => 12, "folder" => 'icon'],
        "logo"         => ["cmne" => 'ASLG', "count" => 3, "folder" => 'logo'],
        "link"         => ["cmne" => 'ASLK', "count" => 8, "folder" => 'link'],
        "script-file"  => ["cmne" => 'ASJS', "count" => 0, "folder" => 'script'],
        "style-file"   => ["cmne" => 'ASCS', "count" => 0, "folder" => 'style'],
    ];
}

function removeOldInstall($db)
{
    $db->truncate('sys_assets');
    $root = realpath(__DIR__ . '/../../public_html/public/assets');

    $depths = ['/*/*/*', '/*/*', '/*'];

    foreach ($depths as $depth) {
        $all = glob($root . $depth);

        foreach ($all as $one) {
            $isIndex = str_ends_with($one, 'index.php');
            if (is_file($one) && (! $isIndex)) {
                @unlink($one);
            }
            if (is_dir($one)) {
                @unlink($one . '/.DS_Store');
                @rmdir($one);
            }
        }
    }

}
