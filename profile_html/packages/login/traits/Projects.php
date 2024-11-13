<?php

Trait Projects {

	public function loadProjects($list) {

		$joinlist = '"' . join('","', $list) . '"';
		$selects = "`handle`, `project`, `cmne`, `name`, `label`, `domain`";

		$sql = "";
		$sql .= "SELECT $selects FROM `projects` ";
		$sql .= "WHERE `deleted` IS NULL ";
		$sql .= "AND (`project` IN ($joinlist))";

		$projects = $this->db->select($sql);

		return $projects;

	}

	public function loadDomains($list) {
		$projects = $this->loadProjects($list);

		$keys = array_map(fn($a) => $a['project'], $projects);
		return array_combine($keys, $projects);

	}

}