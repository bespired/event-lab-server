<?php

class Profile
{

    private $db;
    private $project;
    private $attributes;

    public function __construct($project)
    {
        include_once __DIR__ . "/../utils/MyDB.php";
        include_once __DIR__ . "/../utils/Response.php";

        $this->db      = new MyDB();
        $this->project = $project;

    }

    public function list($router)
    {
        $project = $router->projectChar;
        $selects = [
            'p.handle', 'is_contact', 'visitcount', 'pagecount',
            'firstvistcode', 'firstvistdate', 'firstdevice', 'firstcountry',
            'lastvistcode', 'lastvistdate', 'lastdevice', 'created_at', 'role',
            'email', 'firstname', 'lastname',
            'country_code_1', 'country_name_1', 'state_1', 'city_1',
            'postal_1', 'latitude_1', 'longitude_1',
        ];

        $select = join(',', $selects);

        $sql   = [];
        $sql[] = "SELECT $select FROM `profiles` as p";
        $sql[] = 'LEFT JOIN `accu_contacts`c ON c.profile= p.handle';
        $sql[] = 'LEFT JOIN `accu_geolocation`g ON g.profile= p.handle';

        $sql[] = 'WHERE p.project = "' . $project . '" and ISNULL(p.`deleted_at`)';

        $sql[] = 'ORDER BY p.lastvistdate DESC, p.firstvistdate DESC';
        $sql[] = 'LIMIT 0, 100';

        $results = $this->db->select(join(" ", $sql));

        Response::success($results);
    }

    private function contact_attributes()
    {
        $sql   = [];
        $sql[] = "SELECT * FROM `sys_attributes`";
        $sql[] = "WHERE ISNULL(`deleted_at`)";
        $sql[] = 'AND `table` = "accu_contacts"';
        $mysql = join(" ", $sql);
        return $this->db->selectKey($mysql, 'column');
    }

    private function visit_attributes()
    {
        $sql   = [];
        $sql[] = "SELECT * FROM `sys_globals`";
        $sql[] = "WHERE ISNULL(`deleted_at`)";
        $sql[] = 'AND `table` = "track_timelines"';
        $sql[] = 'AND `timeline` = "page--visit"';
        $mysql = join(" ", $sql);

        return $this->db->selectKey($mysql, 'column');
    }

    public function handle($router)
    {
        $project = $router->projectChar;
        $handle  = $router->payload->profile;

        $attribs = $this->contact_attributes();

        // print_r($attribs);

        $visitor = null;
        $contact = null;
        $visits  = null;
        $devices = null;
        $session = null;

        $profileSelects = [
            'handle', 'cmne', 'is_contact', 'visitcount', 'pagecount',
            'firstvistcode', 'firstvistdate', 'firstdevice', 'firstcountry',
            'lastvistcode', 'lastvistdate', 'lastdevice', 'created_at',
        ];

        $visitSelects = [
            'handle', 'cmne', 'session', 'service', 'created_at', 'time',
            'visitcode', 'visitdate', 'category', 'action', 'value', 'url',
            'attr_1', 'attr_2', 'attr_3', 'attr_4', 'attr_5',
        ];

        $deviceSelects = [
            'hash', 'is_bot', 'mozilla', 'browser', 'browser_engine_version',
            'browser_version', 'like_gecko', 'family', 'sub_family', 'sub_version',
            'platform', 'os', 'os_variant', 'device', 'device_version', 'locale',
        ];

        $select = join(',', $profileSelects);
        $sql    = [];
        $sql[]  = "SELECT $select FROM `profiles`";
        $sql[]  = "WHERE ISNULL(`deleted_at`)";
        $sql[]  = "AND `handle` = \"$handle\"";

        $visitor = $this->db->first(join(" ", $sql));

        if ((bool) $visitor['is_contact']) {
            $sql   = [];
            $sql[] = "SELECT * FROM `accu_contacts`";
            $sql[] = "WHERE ISNULL(`deleted_at`)";
            $sql[] = "AND `profile` = \"$handle\"";
            $mysql = join(" ", $sql);

            $contact = $this->db->renamed($mysql, $attribs);
        }

        $select = join(',', $visitSelects);
        $sql    = [];
        $sql[]  = "SELECT $select FROM `track_timelines`";
        $sql[]  = "WHERE `profile` = \"$handle\"";

        $visits = $this->db->select(join(" ", $sql));

        if ($visits) {
            $visitAttr = $this->visit_attributes();

            $hash    = [];
            $session = [];
            foreach ($visits ?? [] as $visit) {
                $key  = $visit['attr_3'];
                $sess = $visit['session'];

                $hash[$key]     = $key;
                $session[$sess] = $visit['visitdate'];
            }

            $select = join(',', $deviceSelects);
            $hashes = "'" . join("', '", array_keys($hash)) . "'";
            $sql    = [];
            $sql[]  = "SELECT $select FROM `sys_browsers`";
            $sql[]  = "WHERE `hash` IN ($hashes)";

            $keyed = $this->db->select(join(" ", $sql));
            foreach ($keyed as $device) {
                $devices[$device['hash']] = $device;
            }

        }

        $result = [
            'visitor'  => $visitor,
            'contact'  => $contact,
            'visits'   => $visits,
            'devices'  => $devices,
            'sessions' => $session,
        ];

        Response::success($result);
    }

}
