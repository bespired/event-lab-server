<?php

trait Access {

	public function loadAccess($handles, $type, $short = false) {

		$select = $short ? '`handle`, `contact`, `clone`, `role`, `project`' : '*';

		$sql = '';
		$sql .= "SELECT $select FROM `sys_accesses` ";
		$sql .= 'WHERE `deleted_at` IS NULL ';
		$sql .= 'AND (`area_1` = "#" OR `area_2` = "#" OR `area_3` = "#" OR `clone` IS NOT NULL)';
		$sql .= 'AND `contact` IN ( "%" )';

		if (is_array($handles)) {$handles = join('", "', $handles);}
		$sql = str_replace('%', $handles, $sql);

		$sql = str_replace('#', $type, $sql);

		$accesses = $this->db->select($sql);

		$response = [];
		foreach ($accesses ?? [] as $accessArr) {
			$access = (object) $accessArr;
			$response[$access->handle] = $access;
		}

		return $response;
	}

	public function loadClone($handle, $project) {

		$select = 'a.`contact`, a.`role` as role_a, b.`role` as role_b, a.`project`';
		$sql = '';
		$sql .= "SELECT $select FROM `sys_accesses` as a ";
		$sql .= "LEFT JOIN `sys_accesses` as b ON `a`.`clone` = `b`.`handle` ";
		$sql .= 'WHERE (`a`.`deleted_at` IS NULL AND `b`.`deleted_at` IS NULL) ';
		$sql .= 'AND `b`.`contact` = "%"';

		$sql = str_replace('%', $handle, $sql);

		$accesses = $this->db->select($sql);

		$response = [];
		foreach ($accesses ?? [] as $accessArr) {
			$access = (object) $accessArr;
			$response[$access->project] = $access;
		}

		return $response;
	}
}
