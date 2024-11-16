<?php

Trait Projects {

	public function loadProjects($list) {

		$joinlist = '"' . join('","', $list) . '"';
		$selects = "`handle`, `project`, `cmne`, `name`, `label`, `domain`";

		$sql = "";
		$sql .= "SELECT $selects FROM `sys_projects` ";
		$sql .= "WHERE `deleted_at` IS NULL ";
		$sql .= "AND `project` IN ($joinlist)";

		$projects = $this->db->select($sql);

		return $projects;

	}

	public function loadProjectByName($name) {

		$selects = "`handle`, `project`, `cmne`, `name`, `label`, `domain`";
		$sql = "";
		$sql .= "SELECT $selects FROM `sys_projects` ";
		$sql .= "WHERE `deleted_at` IS NULL ";
		$sql .= 'AND `domain` LIKE "%#%" ';

		$sql = str_replace('#', $name, $sql);

		$projects = $this->db->select($sql);

		return $projects;

	}

	public function loadDomains($list) {
		$projects = $this->loadProjects($list);

		$keys = array_map(fn($a) => $a['project'], $projects);
		return array_combine($keys, $projects);

	}

}