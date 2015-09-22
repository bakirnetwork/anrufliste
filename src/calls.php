<?php

function getDateString($call_time) {

	$etime = time() - $call_time;

	$a = array(
		365 * 24 * 60 * 60 => array('Jahr', 'Jahren'),
		 30 * 24 * 60 * 60 => array('Monat', 'Monaten'),
		  7 * 24 * 60 * 60 => array('Woche', 'Wochen'),
		      24 * 60 * 60 => array('Tag', 'Tagen'),
		           60 * 60 => array('Stunde', 'Stunden'),
		                60 => array('Minute', 'Minuten')
	);

	foreach ($a as $secs => $str) {
		$d = $etime / $secs;
		if ($d >= 1) {
			$r = round($d);
			return 'Vor ' . $r . ' ' . ($r > 1 ? $str[1] : $str[0]);
		}
	}

	return 'Gerade eben';
}

function getAssignedUserIDs($call_id) {

	$query = 'SELECT user_id FROM ' . DB_PREFIX . DB_ASSIGNMENTS . ' WHERE call_id=' . $call_id;
	$assignments = queryMySQLData($query);

	$userArray = array();

	while($call_assignment = $assignments->fetch_array()) {
		$userArray[] = $call_assignment['user_id'];
	}

	return $userArray;
}

function getAssignedUserNames($call_id) {

	$query = 'SELECT user_id FROM ' . DB_PREFIX . DB_ASSIGNMENTS . ' WHERE call_id=' . $call_id;
	$result = queryMySQLData($query);

	$assignments = array();
	while($call_assignment = $result->fetch_array()) {
		$name = queryMySQLData('SELECT name FROM ' . DB_PREFIX . DB_USERS . ' WHERE id=' . $call_assignment['user_id'])->fetch_array();
		$assignments[] = $name[0];
	}
	return $assignments;
}

function callDone($id) {

	if (!is_numeric($id)) { return; }

	// check wether call is not already done
	$date = queryMySQLData('SELECT done_date FROM ' . DB_PREFIX . DB_CALLS . ' WHERE id = ' . $id . ';')->fetch_array();
	if ($date[0] == NULL) {
		$date = date('Y-m-d H:i:s');
		$person_id = getLogState();
		$query = 'UPDATE ' . DB_PREFIX . DB_CALLS . ' SET done_date = \'' . $date . '\', done_person = ' . $person_id . ' WHERE id = ' . $id . ';';
		return queryMySQLData($query);
	}
}

function callUndo($id) {

	if (!is_numeric($id)) { return; }

	// check wether call is already done
	$date = queryMySQLData('SELECT done_date FROM ' . DB_PREFIX . DB_CALLS . ' WHERE id = ' . $id . ';')->fetch_array();
	if ($date[0] != NULL) {
		$query = 'UPDATE ' . DB_PREFIX . DB_CALLS . ' SET done_date = NULL, done_person = NULL WHERE id = ' . $id . ';';
		return queryMySQLData($query);
	}
}

function callDelete($id) {

	if (!is_numeric($id)) { return; }

	$query = 'DELETE FROM ' . DB_PREFIX . DB_CALLS . ' WHERE id = ' . $id . ';';

	return queryMySQLData($query);
}

function newCall($contact_forname, $contact_lastname, $contact_phone, $call_subject, $call_notes, $call_assignments) {

	$contact_forname = secureString($contact_forname);
	$contact_lastname = secureString($contact_lastname);
	$contact_phone = secureString($contact_phone);
	$call_subject = secureString($call_subject);
	$call_notes = secureString($call_notes);
	$call_assignments = secureArray($call_assignments);

	$create_datetime = date('Y-m-d H:i:s');
	$call_date       = $create_datetime;
	$create_person   = getLogState();

	$query =
		'INSERT INTO ' . DB_PREFIX . DB_CALLS . '(
		  create_datetime,
		  create_person,
		  call_date,
		  call_subject,
		  call_notes,
		  contact_forname,
		  contact_lastname,
		  contact_phone
		) VALUES (
		  \'' . $create_datetime . '\',
		  ' . $create_person . ',
		  \'' . $call_date . '\',
		  \'' . $call_subject . '\',
		  \'' . $call_notes . '\',
		  \'' . $contact_forname . '\',
		  \'' . $contact_lastname . '\',
		  \'' . $contact_phone . '\'
		);';

	queryMySQLData($query);

	$call_id = queryMySQLData('SELECT * FROM ' . DB_PREFIX . DB_CALLS . ' ORDER BY id DESC LIMIT 1')->fetch_array()['id'];

	$query =
		'INSERT INTO ' . DB_PREFIX . DB_ASSIGNMENTS . ' (
		  call_id,
		  user_id
		) VALUES ';

	$count = count($call_assignments);
	$i = 0;

	foreach ($call_assignments as $number => $user_id) {

		if ($i >= $count-1) {
			$query = $query . '(' . $call_id . ',' . $user_id . '); ';
		} else {
			$query = $query . '(' . $call_id . ',' . $user_id . '),';
		}

		$i++;
	}

	return queryMySQLData($query);
}

function isEditable($call_id, &$assignedUserIDs) {
	$currentUserID = getLogState();
	foreach ($assignedUserIDs as $id) {
		if ($currentUserID == $id) { return true; }
	}
	return false;
}

function getAssignedUsersArray($call_id, &$userArray, &$assignedUserIDs) {
	$userArrays = [];
	foreach ($assignedUserIDs as $id) {
		$userArrays[] = $userArray[$id];
	}
	return $userArrays;
}

function getInitials($fullname) {
	if (empty($fullname)) {
		return '';
	}

	$words    = explode(' ', $fullname); // separate words
	$initials = $words[0][0] . $words[1][0]; // use first char of first two words

	// substr($words[0], 0, 1) . substr($words[1], 0, 1);
	return $initials;
}

function getUserColor($string) {
	$colors = ['orange', 'purple', 'navy', 'blue', 'green', 'olive', 'lime', 'yellow', 'gray', 'red', 'teal', 'maroon'];

	$index = 0;

	// generate number to input string
	for ($i = 0; $i < strlen($string); $i++) {
		$index += ord($string[$i]);
	}

	$index %= count($colors); // mod index to size of `$colors` array
	return $colors[$index]; // return color
}

function getAllUsers() {

	$query = 'SELECT * FROM ' . DB_PREFIX . DB_USERS;
	$users = queryMySQLData($query);

	$userArray = array();

	while($user = $users->fetch_array()) {
		$userArray[$user['id']] = [
			'username' => $user['name'],
			'fullname' => $user['fullname'],
			'initials' => getInitials($user['fullname']),
			'color'    => getUserColor($user['fullname'])
		];
	}

	return $userArray;
}

function getCallDetails($row, &$userArray) {
	$assignedUserIDs = getAssignedUserIDs($row['id']);

	return array(
		'id'               =>   $row['id'],
		'forname'          =>   $row['contact_forname'],
		'lastname'         =>   $row['contact_lastname'],
		'phone'            =>   $row['contact_phone'],
		'date_short'       =>   getDateString(strtotime($row['call_date'])),
		'date'             =>   date('d.m.o H:i', strtotime($row['call_date'])),
		'subject'          =>   $row['call_subject'],
		'notes'            =>   $row['call_notes'],
		'creator'          =>   $userArray[$row['create_person']],
		'assigned'         =>   getAssignedUsersArray($row['id'], $userArray, $assignedUserIDs),
		'editable'         =>   isEditable($row['id'], $assignedUserIDs)
	);
}

function getCallArray() {

	$query = 'SELECT * FROM ' . DB_PREFIX . DB_CALLS . ' WHERE 1 ORDER BY call_date';

	$calls = queryMySQLData($query);

	$callArray = array();

	$userArray = getAllUsers();

	if ($calls != NULL) {
		while($row = $calls->fetch_array()) {
			if ($row['done_date'] == NULL) {
				$callArray[] = getCallDetails($row, $userArray);
			}
		}
	}

	return $callArray;
}

function getDoneCallArray() {

	$query = 'SELECT * FROM ' . DB_PREFIX . DB_CALLS.' WHERE 1 ORDER BY done_date DESC';

	$calls = queryMySQLData($query);

	$callArray = array();

	$userArray = getAllUsers();

	if ($calls != NULL) {
		while($row = $calls->fetch_array()) {
			if ($row['done_date'] != NULL) {
				$callArray[] = getCallDetails($row, $userArray);
			}
		}
	}

	return $callArray;
}
