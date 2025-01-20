<?php

class FormBuilder
{

    private $db;

    public function __construct()
    {
        include_once __DIR__ . '/../utils/MyDB.php';
        include_once __DIR__ . '/../utils/Response.php';

        $this->db = new MyDB();
    }

    public function builder($router)
    {
        // cruds based on roles

        // if (! isset($router->payload->roles) || ! count($router->payload->roles)) {
        //     Response::error('No roles in payload');
        // }
        $project = $router->projectChar;

        $builder = [
            'layouts' => [],
            'fields'  => [],
            'designs' => [],
        ];

        $selects = [
            'handle', 'name', 'label', 'data', 'type',
        ];

        $select = join(',', $selects);

        $sql   = [];
        $sql[] = "SELECT $select FROM `sys_forms`";
        $sql[] = 'WHERE project = "' . $project . '" AND ISNULL(deleted_at)';

        $sql[] = 'ORDER BY type DESC';

        $results = $this->db->select(join(" ", $sql));

        foreach ($results as $key => $result) {
            $key = $result['type'] . 's';

            $result['cast']  = json_decode($result['data']);
            $builder[$key][] = $result;
        }

        Response::success($builder);

    }
}
