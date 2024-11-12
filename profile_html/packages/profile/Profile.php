<?php

Class Profile {

	private $db;
	private $project;
	private $attributes;

	public function __construct($project) {
		include_once __DIR__ . "/../utils/MyDB.php";

		$this->db = new MyDB();
		$this->project = $project;
	}

	public function getAttributes() {
		if ($this->attributes) {
			return $this->attributes;
		}

		$sql = "";
		$sql .= "SELECT * FROM `attributes` ";
		$sql .= "WHERE `attributes`.`project` = '$this->project'";
		$sql .= "AND `attributes`.`deleted` IS NULL";

		$attributes = $this->db->select($sql);
		foreach ($attributes as $attribute) {
			$this->attributes[$attribute['name']] = (object) $attribute;
		}

	}

	public function selectFrom($table) {

		$selecting[] = "`$table`.`handle` as {$table}__handle";
		foreach ($this->attributes as $attribute) {
			$valid = ($attribute->table === $table);
			if ($valid) {
				$name = str_replace('-', '_', $attribute->name);
				$selecting[] = "`$table`.`$attribute->column` as $name";
			}
		}

		return $selecting;

	}

	public function selectTable($table, $handle) {
		$selects = join(", ", $this->selectFrom($table));
		$sql = "SELECT $selects FROM `tags` ";
		$sql .= "WHERE `$table`.`profile` = '$handle' ";
		$sql .= "AND `tags`.`project` = '$this->project' ";

		return $sql;
	}

	public function getProfileViaContact($key) {

		$this->getAttributes();

		$profileSelect = $this->selectFrom('profiles');
		$contactSelect = $this->selectFrom('contacts');

		$selects = join(", ", array_merge($profileSelect, $contactSelect));

		$sql = "";
		$sql .= "SELECT $selects FROM `contacts` ";
		$sql .= "INNER JOIN `profiles` ON `contacts`.`profile` = `profiles`.`handle`";
		$sql .= "WHERE `contacts`.`project` = '$this->project' ";
		$sql .= "AND `contacts`.`email` = '$key' ";
		$sql .= "AND `profiles`.`deleted` IS NULL";

		$results = $this->db->first($sql);

		// if no-one found...
		// return null ?

		$handle = $results['profiles__handle'];
		$return = ['id' => $handle];

		foreach ($results as $key => $value) {
			if (str_starts_with($key, 'contact')) {
				$names = explode("__", $key, 2);
				$field = $names[1];

				$return['contact'][$field] = $value;
			}
		}

		//

		$sql = $this->selectTable('tags', $handle);
		$results = $this->db->first($sql);

		foreach ($results as $key => $value) {

			if (str_starts_with($key, 'tag_')) {
				$names = explode("__", $key, 2);
				$field = $names[1];

				$mne = 'tag--' . str_replace('_', '-', $field);

				if (isset($this->attributes[$mne])) {
					$attr = $this->attributes[$mne];
					$name = $attr->name;
					$extra = $attr->extra;
					$return['tags'][$name] = [
						'handle' => $attr->handle,
						'label' => $attr->label,
						'cmne' => $attr->cmne,
						'group' => json_decode($extra)->group,
						'attached' => $value == '1' ? true : false,
						'value' => $value,
					];
				} else {
					$return['tags'][$field] = $field . ' ' . $mne;
				}

			}
		}

		// $return['tags'] = $results;

		$return['consents'] = [];
		$return['journeys'] = [];
		$return['timeline'] = [];

		// $return['attributes'] = $this->attributes;

		// print_r($results);

		return $return;
	}

}