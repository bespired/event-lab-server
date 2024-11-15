<?php

trait Contacts {
	public function loadContacts($handle) {
		$sql = '';
		$sql .= 'SELECT * FROM `accu_contacts` ';
		$sql .= 'WHERE `deleted_at` IS NULL ';
		$sql .= 'AND (`email` = "%" OR `handle` = "%" )';

		$sql = str_replace('%', $handle, $sql);

		$contacts = $this->db->select($sql);

		return $contacts;
	}
}
