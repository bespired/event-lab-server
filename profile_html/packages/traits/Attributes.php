<?php

Trait Attributes {

	public function loadAccessAttributes() {

		// todo: if in cache then return cache

		$sql = "";
		$sql .= 'SELECT * FROM `sys_globals` ';
		$sql .= 'WHERE `deleted_at` IS NULL ';
		$sql .= 'AND `table` = "access" ';

		$attrs = $this->db->select($sql);

		$keys = array_map(fn($a) => $a['column'], $attrs);
		return array_combine($keys, $attrs);

	}

}