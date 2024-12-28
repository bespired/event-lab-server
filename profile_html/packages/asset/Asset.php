<?php

class Asset
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
        $project   = $router->projectChar;
        $assetType = $router->payload->assetType;

        $selects = [
            'id', 'handle', 'cmne', 'type', 'mimetype',
            'version', 'url', 'name', 'label', 'tags',
        ];

        $select = join(',', $selects);

        $sql   = [];
        $sql[] = "SELECT $select FROM `sys_assets` as a";

        $sql[] = "WHERE ISNULL(`deleted_at`) ";
        $sql[] = sprintf('AND `project` = "%s" ', $project);
        $sql[] = sprintf('AND `type` = "%s";', $assetType);

        $results = $this->db->select(join(" ", $sql));

        // print_r($results);
        // exit;

        Response::success($results);
    }

}
