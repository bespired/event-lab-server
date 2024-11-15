<?php

Class AlterTag {

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
		$sql .= "SELECT * FROM `sys_attributes` ";
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
		foreach (['group', 'label', 'description'] as $name) {
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

	private function groupExists($groupname) {
		return isset($this->attributes['group']) && isset($this->attributes['group'][$groupname]);
	}

	private function collect($prop) {
		$attrtags = $this->attributes['tag'];
		foreach ($attrtags as $tag) {
			$tags[] = $tag->$prop;
		}
		return $tags;
	}

	private function findFreeTagId() {
		$tags = $this->collect('column');
		$count = 1;
		while (in_array("tag_$count", $tags)) {
			$count++;
		}
		return $count;
	}

	private function findFreeName($label) {
		$tags = $this->collect('name');
		$name = substr('tag--' . glob_slug($label), 0, 128);

		if (!in_array($name, $tags)) {
			return $name;
		}

		$count = 1;
		do {
			$name = substr('tag--' . ($count++) . '-' . glob_slug($label), 0, 128);
		} while (in_array($name, $tags));

		return $name;
	}

	private function findFreeLabel($label) {
		$tags = $this->collect('label');

		if (!in_array($label, $tags)) {
			return $label;
		}

		$count = 2;
		do {
			$name = substr($label, 0, 120) . ' ' . ($count++);
		} while (!!in_array($name, $tags));

		return $name;
	}

	public function handle($router) {

		// print_r($router);
		// print_r($router->payload);
		$this->project = $router->projectChar;
		$this->getAttributes();

		$this->validate($router);

		// print_r($this->attributes);

		if (!$this->groupExists($router->payload->group)) {
			// create group
			$swapPayload = clone ($router->payload);
			$router->payload = (object) [
				"label" => glob_deslug($swapPayload->group, 'ucwords'),
				"description" => "This is a group made for tag " . $swapPayload->label,
			];

			include_once "AlterGroup.php";
			(new AlterGroup)->handle($router);

			$router->payload = clone ($swapPayload);
		}

		if ($update = $this->isUpdate($router)) {
			// update via $update
			// echo "update tag $update\n";

			$tags = $this->collect($update);
			if (!in_array($router->payload->$update, $tags)) {
				echo "Houston, we have a problem, where does not exists..\n";
				exit;
			}

			$slots['label'] = $this->findFreeLabel($router->payload->label);
			$slots['description'] = substr($router->payload->description, 0, 128);

			$slots['extra'] = addslashes(json_encode(['group' => $router->payload->group]));

			$where = [];
			$where[$update] = $router->payload->$update;
			$this->db->update('attributes', $slots, $where);

		} else {
			// create
			// echo "Create tag \n";

			$tagId = $this->findFreeTagId();
			$cmne = 'TT' . glob_base36($tagId);

			$slots['handle'] = Handle::make($tagId, $cmne, 'attr');
			$slots['project'] = $this->project;
			$slots['table'] = 'tags';

			$slots['label'] = $this->findFreeLabel($router->payload->label);
			$slots['description'] = substr($router->payload->description, 0, 128);

			$slots['name'] = $this->findFreeName($router->payload->label);

			$slots['column'] = "tag_$tagId"; // 'tag_*'; // find free tag_
			$slots['cmne'] = $cmne; // 'TTG*'; // find a TTG* or TTA* or TTB* (base36)

			$slots['extra'] = addslashes(json_encode(['group' => $router->payload->group]));
			$slots['datatype'] = 'schrodinger';
			$slots['valuetype'] = 'single';

			$this->db->insert('attributes', $slots);

		}

		// (
		//     [label] => My Fantastic Tag
		//     [description] => This is a Fantastic Tag
		//     [group] => base-tags
		//
		// )

	}

}