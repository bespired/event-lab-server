<?php

Class AlterGroup {

	private $db;
	private $project;
	private $attributes;

	public function __construct() {
		include_once __DIR__ . "/../utils/MyDB.php";
		include_once __DIR__ . "/../utils/Handle.php";

		$this->db = new MyDB();

	}

	private function getAttributes() {
		if ($this->attributes) {
			return $this->attributes;
		}

		$sql = "";
		$sql .= "SELECT * FROM sys_attributes ";
		$sql .= "WHERE `sys_attributes`.`project` = '$this->project'";
		$sql .= "AND `sys_attributes`.`deleted_at` IS NULL";

		$attributes = $this->db->select($sql);
		foreach ($attributes as $attribute) {
			$keyname = $attribute['name'];
			$key = explode('--', $keyname, 2)[0];
			$name = explode('--', $keyname, 2)[1];
			$this->attributes[$key][$name] = (object) $attribute;
		}

	}

	private function validate($router) {
		foreach (['label', 'description'] as $name) {
			if (!isset($router->payload->$name)) {
				echo "missing $name in payload";
				exit;
			}
		}
	}

	private function isUpdate($router) {
		foreach (['handle', 'name', 'cmne', 'column'] as $name) {
			if (isset($router->payload->$name)) {
				return $name;
			}
		}
		return null;
	}

	private function collect($prop) {
		$attrgroups = $this->attributes['group'];
		foreach ($attrgroups as $group) {
			$groups[] = $group->$prop;
		}
		return $groups;
	}

	private function findFreeGroupId() {
		$list = str_split('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		$attrgroups = $this->attributes['group'];
		$count = 1;
		while (!!in_array("GBT" . $list[$count], array_column($attrgroups, 'cmne'))) {
			$count++;
		}
		return $list[$count];
	}

	private function findFreeName($label) {
		$groups = $this->collect('name');
		$name = substr('group--' . glob_slug($label), 0, 128);

		if (!in_array($name, $groups)) {
			return $name;
		}

		$count = 1;
		do {
			$name = substr('group--' . ($count++) . '-' . glob_slug($label), 0, 128);
		} while (!!in_array($name, $groups));

		return $name;
	}

	private function findFreeLabel($label) {
		$groups = $this->collect('label');

		if (!in_array($label, $groups)) {
			return $label;
		}

		$count = 2;
		do {
			$name = substr($label, 0, 120) . ' ' . ($count++);
		} while (in_array($name, $groups));

		return $name;
	}

	public function handle($router) {

		$this->project = $router->projectChar;
		$this->getAttributes();

		$this->validate($router);

		if ($update = $this->isUpdate($router)) {

			$groups = $this->collect($update);
			if (!in_array($router->payload->$update, $groups)) {
				echo "Houston, we have a problem, where does not exists..\n";
				exit;
			}

			$slots['label'] = $this->findFreeLabel($router->payload->label);
			$slots['description'] = substr($router->payload->description, 0, 128);

			$where = [];
			$where[$update] = $router->payload->$update;
			$this->db->update('attributes', $slots, $where);

		} else {

			$groupId = $this->findFreeGroupId();
			$cmne = 'GBT' . $groupId;

			$slots['handle'] = Handle::make($groupId, $cmne, 'attr');
			$slots['project'] = $this->project;
			$slots['table'] = 'groups';

			$slots['label'] = $this->findFreeLabel($router->payload->label);
			$slots['description'] = substr($router->payload->description, 0, 128);

			$slots['name'] = $this->findFreeName($router->payload->label);

			$slots['cmne'] = $cmne;

			$slots['datatype'] = 'relation';
			$slots['valuetype'] = 'single';

			$this->db->insert('attributes', $slots);

		}

		// (
		// "label": "Second Tag",
		// "description": "This is a group for tag",
		// "cmne" : "GBT2"
		//
		// )

	}

}