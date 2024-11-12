<?php

trait Access
{
    public function loadAccess($handles, $type)
    {
        $sql = '';
        $sql .= 'SELECT * FROM `proj_accesses` ';
        $sql .= 'WHERE `deleted` IS NULL ';
        $sql .= 'AND (`area_1` = "#" OR `area_2` = "#" OR `area_3` = "#" OR `clone` IS NOT NULL)';
        $sql .= 'AND `contact` IN ( % )';

        $sql = str_replace('%', join(', ', $handles), $sql);
        $sql = str_replace('#', $type, $sql);

        $accesses = $this->db->select($sql);

        $response = [];
        foreach ($accesses as $accessArr) {
            $access = (object) $accessArr;

            $response[$access->handle] = $access;
        }

        return $response;
    }
}
