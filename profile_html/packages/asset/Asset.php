<?php

class Asset
{

    private $db;
    private $project;
    private $attributes;
    private $singulars;

    public function __construct($project)
    {
        include_once __DIR__ . "/../utils/MyDB.php";
        include_once __DIR__ . "/../utils/Response.php";

        $this->db      = new MyDB();
        $this->project = $project;

        $this->singulars = [
            "illustrations" => "illustration",
            "pictures"      => "picture",
            "email-images"  => "email-image",
            "email"         => "email-image",
            "images"        => "image",
            "icons"         => "icon",
            "logos"         => "logo",
            "links"         => "link",
            "documents"     => "document",
            "font-file"     => "font-file",
            "font-glyph"    => "font-glyph",
            "style-file"    => "style-file",
            "script-file"   => "script-file",
            "private"       => "private",
        ];
    }

    public function list($router)
    {
        $project   = $router->projectChar;
        $assetType = $this->singular($router->payload->assetType);

        $selects = [
            'handle', 'cmne', 'type', 'mimetype',
            'version', 'url', 'name', 'label', 'tags',
        ];

        $select = '`' . join('`,`', $selects) . '`';

        $sql   = [];
        $sql[] = "SELECT $select FROM `sys_assets` as a";

        $sql[] = "WHERE ISNULL(`deleted_at`)";
        $sql[] = sprintf('AND `project` = "%s" ', $project);
        $sql[] = sprintf('AND `type` = "%s"', $assetType);
        $sql[] = "ORDER BY `id` DESC";

        $results = $this->db->select(join(" ", $sql));

        foreach ($results ?? [] as $idx => $result) {
            $results[$idx]['tags'] = json_decode($result['tags']);
        }

        Response::success($results);
    }

    private function singular($plural)
    {
        if (! isset($this->singulars[$plural])) {
            return $plural;
        }
        return $this->singulars[$plural];
    }

}
