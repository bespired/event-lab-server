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

    public function handle($router)
    {
        $project = $router->projectChar;
        $handle  = $router->payload->profile;

        $sql     = [];
        $sql[]   = "SELECT * FROM `sys_attributes`";
        $sql[]   = "WHERE ISNULL(`deleted_at`)";
        $sql[]   = 'AND `table` = "accu_contacts"';
        $mysql   = join(" ", $sql);
        $attribs = $this->db->selectKey($mysql, 'column');

        // print_r($attribs);

        $visitor = null;
        $contact = null;
        $visits  = null;

        $profileSelects = [
            'handle', 'cmne', 'is_contact', 'visitcount', 'pagecount',
            'firstvistcode', 'firstvistdate', 'firstdevice', 'firstcountry',
            'lastvistcode', 'lastvistdate', 'lastdevice', 'created_at',
        ];

        $visitSelects = [
            'handle', 'cmne', 'session', 'service', 'created_at', 'time',
            'visitcode', 'visitdate', 'category', 'action', 'value', 'url',
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

            // $contact = $this->db->first(join(" ", $sql));

            $contact = $this->db->renamed($mysql, $attribs);

        }

        $select = join(',', $visitSelects);
        $sql    = [];
        $sql[]  = "SELECT $select FROM `track_timelines`";
        $sql[]  = "WHERE `profile` = \"$handle\"";

        $visits = $this->db->select(join(" ", $sql));

        $result = [
            'visitor' => $visitor,
            'contact' => $contact,
            'visits'  => $visits,
        ];

        Response::success($result);
    }

}
