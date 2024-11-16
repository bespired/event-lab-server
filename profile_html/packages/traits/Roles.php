<?php

Trait Roles {

	public function loadCruds($roles, $attrs, $project = 'a') {

		$selects = ['role', 'label', 'cmne'];
		foreach ($attrs as $attr => $name) {
			$selects[] = sprintf('`%s` as "%s"', $attr, $name);
		}

		$select = join(", ", $selects);
		$role = join('", "', $roles);

		$sql = "";
		$sql .= 'SELECT @ FROM `crud_roles` ';
		$sql .= 'WHERE `deleted_at` IS NULL ';
		$sql .= 'AND `role` IN ("%") ';
		$sql .= 'AND `project` = "#" ';

		$sql = str_replace('@', $select, $sql);
		$sql = str_replace('%', $role, $sql);
		$sql = str_replace('#', $project, $sql);

		$cruds = $this->db->select($sql);

		$keys = array_map(fn($a) => $a['role'], $cruds);
		return array_combine($keys, $cruds);

	}

}