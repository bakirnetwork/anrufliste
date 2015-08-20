<?php

header('Content-Type: text/html; charset=utf-8');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/bakir-anrufliste-config.php');
require(__DIR__ . '/calls.php');

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);
$slack = new Maknz\Slack\Client(SLACK_WEBHOOK, ['link_names' => true]);

initLogSys();

$page = 'login';
$login_error = false;
$context = array();

if (isset($_GET['logout'])) {
	logUserOut();
	header('Location: /');
}

if (isset($_GET['login'])) {
	$username = $_POST['username'];
	$password = $_POST['password'];
	$keeplog  = $_POST['keeplog'];

	logUserIn($username, $password, $keeplog);
	$login_error = true;
	//header('Location: /');
}

if (isset($_GET['newcall'])) {
	if (
		isset($_POST['forename']) &&
		isset($_POST['lastname']) &&
		isset($_POST['phone']) &&
		isset($_POST['subject'])
	) {
		newCall($_POST['forename'], $_POST['lastname'], $_POST['phone'], $_POST['subject'], $_POST['notes'], $_POST['assignments']);

		function assignedNames() {
			$id_query = '';

			foreach($_POST['assignments'] as $assignment) {
				$id_query = $id_query . $assignment . ',';
			}

			$id_query = trim($id_query, ',');

			$query = 'SELECT * FROM ' . DB_PREFIX . DB_USERS . ' WHERE id in (' . $id_query . ')';
			$user = queryMySQLData($query);
			$namelist = [];

			while($row = $user->fetch_array()) {
				$namelist[] = $row['name'];
			}

			return $namelist;
		}

		// generate and send Slack message
		$namelist = assignedNames();
		$slackPoke = '';
		$anrede = 'Dir';

		if (count($namelist) > 1) {
			$anrede = 'Euch';
		}

		foreach($namelist as $i=>$name) {
			$slackPoke .= '@' . $name;

			if ($i <= count($namelist) -3) {
				$slackPoke .= ', ';
			} elseif ($i == count($namelist) -2) {
				$slackPoke .= ' und ';
			}
		}

		$message  = 'Hallo ' . $slackPoke . '! ';
		$message .= $anrede . ' wurde ein *neuer Anruf* ';
		$message .= 'von @' . getSingleUserData('name') . ' zugewiesen. ';
		$message .= '<http://' . $_SERVER['SERVER_NAME'] . '|Hier ansehen> für Details.';

		$slack->send($message);

	}

	header('Location: /');
}

if (isset($_GET['done'])) {
	if (isset($_POST['done_id'])) {
		callDone($_POST['done_id']);
	}
	header('Location: /');
}

if (isset($_GET['undo'])) {
	if (isset($_POST['undo_id'])) {
		callUndo($_POST['undo_id']);
	}
	header('Location: /');
}


if (getLogState()) {

	if (isset($_GET['account'])) {

		if (isset($_POST['password_old']) && isset($_POST['password_new']) && isset($_POST['password_repeat'])) {

			if ($_POST['password_new'] == $_POST['password_repeat']) {
				$password_result = !resetPassword($_POST['password_old'], $_POST['password_new']);
			} else {
				$password_result = true;
			}

			if ($password_result == false) {
				header('Location: /');
			}
		}

		$page = 'account';
		$context = array(
			'form_path'            => '?account=true',
			'form_password_old'    => 'password_old',
			'form_password_new'    => 'password_new',
			'form_password_repeat' => 'password_repeat',
			'password_error'       => isset($password_result) ? $password_result : false
		);

	} else {

		// set assignment ids
		$query = 'SELECT * FROM ' . DB_PREFIX . DB_USERS . ' WHERE 1';
		$user = queryMySQLData($query);
		$assignlist = array();

		while($row = $user->fetch_array()) {
			$assignlist[] = array(
				'id'   => $row['id'],
				'name' => $row['name']
			);
		}

		$context = array(
			'user_name'       => getSingleUserData('name'),
			'user_id'         => getLogState(),
			'calls_undone'    => getCallArray(),
			'calls_done'      => getDoneCallArray(),
			'users'           => $assignlist,
			'account_path'    => '?account=true',
			'done_path'       => '?done=true',
			'undo_path'       => '?undo=true',
			'form_path'       => '?newcall=true',
			'form_forename'   => 'forename',
			'form_lastname'   => 'lastname',
			'form_phone'      => 'phone',
			'form_subject'    => 'subject',
			'form_assignment' => 'assignments',
			'form_notes'      => 'notes'
		);

		$page = 'home';
	}
} else {
	$context = array(
		'form_path'          => '?login=true',
		'form_user_name'     => 'username',
		'form_password_name' => 'password',
		'form_keeplog_name'  => 'keeplog',
		'login_error'        => $login_error
	);
	$page = 'login';
}

if ($page == 'home') {
	echo $twig->render('home.twig', $context);
} else if ($page == 'login') {
	echo $twig->render('login.twig', $context);
} else if ($page == 'account') {
	echo $twig->render('account.twig', $context);
}
