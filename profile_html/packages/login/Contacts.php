<?php

Trait Contacts {

	public function loadContacts($handle) {

		$sql = "";
		$sql .= "SELECT * FROM `acc_contacts` ";
		$sql .= "WHERE `deleted` IS NULL ";
		$sql .= 'AND (`email` = "%" OR `handle` = "%" )';

		$sql = str_replace('%', $handle, $sql);

		$contacts = $this->db->select($sql);

		return $contacts;

	}

}