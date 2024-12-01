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
            'is_contact', 'visitcount', 'pagecount', 'firstvistcode', 'firstvistdate', 'firstdevice',
            'firstcountry', 'lastvistcode', 'lastvistdate', 'lastdevice', 'created_at', 'role',
            'email', 'firstname', 'lastname', 'country_code_1', 'country_name_1', 'state_1', 'city_1',
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

    // public function getAttributes()
    // {
    //     if ($this->attributes) {
    //         return $this->attributes;
    //     }

    //     $sql = "";
    //     $sql .= "SELECT * FROM `sys_attributes` ";
    //     $sql .= "WHERE `sys_attributes`.`project` = '$this->project'";
    //     $sql .= "AND `sys_attributes`.`deleted_at` IS NULL";

    //     $attributes = $this->db->select($sql);
    //     foreach ($attributes as $attribute) {
    //         $this->attributes[$attribute['name']] = (object) $attribute;
    //     }

    // }

    // public function selectFrom($table)
    // {

    //     $selecting[] = "`$table`.`handle` as {$table}__handle";
    //     foreach ($this->attributes as $attribute) {
    //         $valid = ($attribute->table === $table);
    //         if ($valid) {
    //             $name        = str_replace('-', '_', $attribute->name);
    //             $selecting[] = "`$table`.`$attribute->column` as $name";
    //         }
    //     }

    //     return $selecting;

    // }

    // public function selectTable($table, $handle)
    // {
    //     $selects = join(", ", $this->selectFrom($table));
    //     $sql     = "SELECT $selects FROM `tags` ";
    //     $sql .= "WHERE `$table`.`profile` = '$handle' ";
    //     $sql .= "AND `tags`.`project` = '$this->project' ";

    //     return $sql;
    // }

    // public function getProfileViaContact($key)
    // {

    //     $this->getAttributes();

    //     $profileSelect = $this->selectFrom('profiles');
    //     $contactSelect = $this->selectFrom('accu_contacts');

    //     $selects = join(", ", array_merge($profileSelect, $contactSelect));

    //     $sql = "";
    //     $sql .= "SELECT $selects FROM `contacts` ";
    //     $sql .= "INNER JOIN `profiles` ON `accu_contacts`.`profile` = `profiles`.`handle`";
    //     $sql .= "WHERE `accu_contacts`.`project` = '$this->project' ";
    //     $sql .= "AND `accu_contacts`.`email` = '$key' ";
    //     $sql .= "AND `profiles`.`deleted_at` IS NULL";

    //     $results = $this->db->first($sql);

    //     // if no-one found...
    //     // return null ?

    //     $handle = $results['profiles__handle'];
    //     $return = ['id' => $handle];

    //     foreach ($results as $key => $value) {
    //         if (str_starts_with($key, 'contact')) {
    //             $names = explode("__", $key, 2);
    //             $field = $names[1];

    //             $return['contact'][$field] = $value;
    //         }
    //     }

    //     //

    //     $sql     = $this->selectTable('tags', $handle);
    //     $results = $this->db->first($sql);

    //     foreach ($results as $key => $value) {

    //         if (str_starts_with($key, 'tag_')) {
    //             $names = explode("__", $key, 2);
    //             $field = $names[1];

    //             $mne = 'tag--' . str_replace('_', '-', $field);

    //             if (isset($this->attributes[$mne])) {
    //                 $attr                  = $this->attributes[$mne];
    //                 $name                  = $attr->name;
    //                 $extra                 = $attr->extra;
    //                 $return['tags'][$name] = [
    //                     'handle'   => $attr->handle,
    //                     'label'    => $attr->label,
    //                     'cmne'     => $attr->cmne,
    //                     'group'    => json_decode($extra)->group,
    //                     'attached' => $value == '1' ? true : false,
    //                     'value'    => $value,
    //                 ];
    //             } else {
    //                 $return['tags'][$field] = $field . ' ' . $mne;
    //             }

    //         }
    //     }

    //     // $return['tags'] = $results;

    //     $return['consents'] = [];
    //     $return['journeys'] = [];
    //     $return['timeline'] = [];

    //     // $return['attributes'] = $this->attributes;

    //     // print_r($results);

    //     return $return;
    // }

}
