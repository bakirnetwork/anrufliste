<?php

function getCallArray() {
	initTable(DB_PREFIX.DB_CALLS, SQL_CALLS);
    
    $query = 'SELECT * FROM '.DB_PREFIX.DB_CALLS.' WHERE 1 ORDER BY call_date';
    
    $calls = queryMySQLData($query);
	
	$callArray = array();
    
	if ($calls != NULL) {
	
		while($row = $calls->fetch_array()) {
			if ($row['done_date'] == NULL) {
				
				$callArray[] = array('id' => $row['id'],
									'vorname' => $row['contact_forname'],
									'nachname' => $row['contact_lastname'],
									'telefon' => $row['contact_phone'],
									'datum' => date('j. M, h:i', strtotime($row['call_date'])),
									'betreff' => $row['call_subject'],
									'personen' => getAssignedUserIDs($row['id']));
			}
		}
	
	}

	return $callArray;
}

function getDoneCallArray() {
	initTable(DB_PREFIX.DB_CALLS, SQL_CALLS);

	$query = 'SELECT * FROM '.DB_PREFIX.DB_CALLS.' WHERE 1 ORDER BY call_date';

	$calls = queryMySQLData($query);

	$callArray = array();

	if ($calls != NULL) {

		while($row = $calls->fetch_array()) {
			if ($row['done_date'] != NULL) {
				$callArray[] = array('id' => $row['id'],
									'vorname' => $row['contact_forname'],
									'nachname' => $row['contact_lastname'],
									'telefon' => $row['contact_phone'],
									'datum' => date('j. M, h:i', strtotime($row['call_date'])),
									'betreff' => $row['call_subject'],
									'personen' => getAssignedUserIDs($row['id']));
			}
		}

	}

	return $callArray;
}

function getAssignedUserIDs($call_id) {
	$query = 'SELECT user_id FROM '.DB_PREFIX.DB_ASSIGNMENTS.' WHERE call_id='.$call_id;
	$assignments = queryMySQLData($query);

	$userArray = array();

	while($call_assignment = $assignments->fetch_array()) {
		$userArray[] = $call_assignment['user_id'];
	}

	return $userArray;
}

function getAssignedUserNames($call_id) {
	$query = 'SELECT user_id FROM '.DB_PREFIX.DB_ASSIGNMENTS.' WHERE call_id='.$call_id;
	$result = queryMySQLData($query);

	$assignments = array();
	while($call_assignment = $result->fetch_array()) {
		$name = queryMySQLData('SELECT name FROM '.DB_PREFIX.DB_USERS.' WHERE id='.$call_assignment['user_id'])->fetch_array();
		$assignments[] = $name[0];
	}
	return $assignments;
}

function callDone($id) {
	initTable(DB_PREFIX.DB_CALLS, SQL_CALLS);

	// check wether call is not already done
	$date = queryMySQLData('SELECT done_date FROM '.DB_PREFIX.DB_CALLS.' WHERE id = '.$id.';')->fetch_array();
	if ($date[0] == NULL) {
		$date = date('Y-m-d H:i:s');
		$person_id = getLogState();
		$query = 'UPDATE '.DB_PREFIX.DB_CALLS.' SET done_date = \''.$date.'\', done_person = '.$person_id.' WHERE id = '.$id.';';
		queryMySQLData($query);
	}
}

function callUndo($id) {
	initTable(DB_PREFIX.DB_CALLS, SQL_CALLS);

	// check wether call is already done
	$date = queryMySQLData('SELECT done_date FROM '.DB_PREFIX.DB_CALLS.' WHERE id = '.$id.';')->fetch_array();
	if ($date[0] != NULL) {
		$query = 'UPDATE '.DB_PREFIX.DB_CALLS.' SET done_date = NULL, done_person = NULL WHERE id = '.$id.';';
		queryMySQLData($query);
	}
}

function newCall($contact_forname, $contact_lastname, $contact_phone, $call_subject, $call_notes, $call_assignments) {	
	initTable(DB_PREFIX.DB_CALLS, SQL_CALLS);
	initTable(DB_PREFIX.DB_ASSIGNMENTS, SQL_ASSIGNMENTS);

	$create_datetime = date('Y-m-d H:i:s');
	$call_date = $create_datetime;
	$create_person = getLogState();

	$query = 'INSERT INTO '.DB_PREFIX.DB_CALLS.'(
				create_datetime, 
				create_person, 
				call_date, 
				call_subject, 
				call_notes, 
				contact_forname, 
				contact_lastname, 
				contact_phone
			) VALUES (
				\''.$create_datetime.'\',
				'.$create_person.',
				\''.$call_date.'\',
				\''.$call_subject.'\',
				\''.$call_notes.'\',
				\''.$contact_forname.'\',
				\''.$contact_lastname.'\',
				\''.$contact_phone.'\'
			);';

	queryMySQLData($query);

	$call_id = queryMySQLData('SELECT * FROM '.DB_PREFIX.DB_CALLS.' ORDER BY id DESC LIMIT 1')->fetch_array()['id'];

	$query = 'INSERT INTO '.DB_PREFIX.DB_ASSIGNMENTS.' (
					call_id,
					user_id
				) VALUES ';

	$count = count($call_assignments);
	$i = 0;

	foreach ($call_assignments as $number => $user_id) {

		if ($i >= $count-1) {
			$query = $query . '('.$call_id.','.$user_id.'); ';
		} else {
			$query = $query . '('.$call_id.','.$user_id.'),';
		}

		$i++;
	}

	// e.g.: $query = "INSERT INTO bc_assignments ( call_id, user_id ) VALUES (32,1),(32,2),(32,3),(32,4),(32,5),(32,7)"

	queryMySQLData($query);
}

?>
