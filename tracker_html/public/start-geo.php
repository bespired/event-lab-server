<?php

include_once __DIR__ . '/../utils/MyDB.php';
include_once __DIR__ . '/../utils/MyCache.php';
include_once __DIR__ . '/../utils/Handle.php';
include_once __DIR__ . '/../utils/Agent.php';
include_once __DIR__ . '/../utils/Tools.php';

// Task of this script is to read from REDIS
// tokens list and store the tokens on the timeline
//
// start only when not already doing this task
// and continue until token list is empty

$redis = new MyCache();

if ($redis->isHT('geo')) {
    $redis->close();
    exit;
}

$db = new MyDB();

$redis->htStart('geo');

$geo = $redis->topGeo();
while ($geo) {

    $redis->storeLog($geo);

    // Handle the atomic token...
    // split atomic token...

    list($profile, $realip, $time) = explode('||', $geo, 4);

    if ($realip === '192.168.65.1') {
        $realip = '77.161.128.35';
    }

    // -- Does it need creation of update?
    // if ip and profile exists then done.

    $project = substr($profile, 0, 1);

    $selects = '';
    $selects .= '`handle`, `profile`, ';
    $selects .= '`is_changed`, `is_new`, ';
    $selects .= '`real_ip_1`, `real_ip_2`, `real_ip_3`, `real_ip_4`, `real_ip_5`';

    $sql = '';
    $sql .= "SELECT $selects FROM `accu_geolocation` ";
    $sql .= 'WHERE `project` = "' . $project . '" ';
    $sql .= 'AND `profile` = "' . $profile . '" ';

    $found = $db->first($sql);

    if (! $found) {

        $redis->storeLog('New location ' . $realip);
        $geodata = geoData($realip);
        trackGeo($geodata, true);
        geoProfile($geodata);

    } else {

        $ips = [
            'Entry 0 indexed',
            $found['real_ip_1'],
            $found['real_ip_2'],
            $found['real_ip_3'],
            $found['real_ip_4'],
            $found['real_ip_5'],
        ];

        if (! in_array($realip, $ips)) {

            // Profile is known, but IP is new.
            $redis->storeLog('Profile is known, but IP is new ' . $realip);
            $geodata = geoData($realip);

            $slot = array_search(null, $ips);
            trackGeo($geodata, false, $slot ?? 5);
        }

    }

    $geo = $redis->topGeo();
}

$redis->htEnd('geo');
$redis->close();
$db->close();

exit;
// --''

function geoData($realip)
{
    global $db, $server;

    // db has dotenv for convenience
    $geotoken = $db->env->geolocationDb;
    $url      = "https://geolocation-db.com/json/$geotoken/$realip";

    $geojson = file_get_contents($url);

    $geo = json_decode($geojson);

    return $geo;
}

function trackGeo($geodata, $isNew, $slot = 1)
{
    global $db, $time, $profile, $realip;

    $columns = [];

    $columns['is_changed'] = 1;
    $columns['is_new']     = $isNew ? 1 : 0;

    $columns['real_ip_' . $slot]      = $realip;
    $columns['count_' . $slot]        = 1;
    $columns['country_code_' . $slot] = $geodata->country_code;
    $columns['country_name_' . $slot] = $geodata->country_name;
    $columns['state_' . $slot]        = $geodata->state;
    $columns['city_' . $slot]         = $geodata->city;
    $columns['postal_' . $slot]       = $geodata->postal;
    $columns['latitude_' . $slot]     = $geodata->latitude;
    $columns['longitude_' . $slot]    = $geodata->longitude;

    if ($isNew) {
        $cmne   = 'GLCN';
        $handle = Handle::create('geolocation', $cmne, $time);

        $columns['handle']  = $handle;
        $columns['profile'] = $profile;
        $columns['cmne']    = $cmne;
        $columns['project'] = substr($profile, 0, 1);

        $db->insert('accu_geolocation', $columns);
    } else {
        $db->update('accu_geolocation', $columns, ['profile' => $profile]);
    }

}

function geoProfile($geodata)
{
    global $db, $profile;
    $columns = [];

    $columns['firstcountry'] = $geodata->country_code;

    $db->update('profiles', $columns, ['handle' => $profile]);
}
