<?php

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/bakir-anrufliste-config.php');
require(__DIR__ . '/calls.php');

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);

initLogSys();

$page = 'login';
$context = array();

if (isset($_GET['logout'])) {
	logUserOut();
	header('Location: index.php');
}

if (isset($_GET['login'])) {
	if (isset($_POST['username']) && isset($_POST['password'])) {
		logUserIn($_POST['username'], $_POST['password'], $_POST['keeplog']);
		header('Location: index.php');
	}
}

if (isset($_GET['newcall'])) {
	if (
		isset($_POST['forename']) &&
		isset($_POST['lastname']) &&
		isset($_POST['phone']) &&
		isset($_POST['subject'])
	) {
		newCall($_POST['forename'], $_POST['lastname'], $_POST['phone'], $_POST['subject'], $_POST['notes'], $_POST['assignments']);
	}
	header('Location: index.php');
}

if (isset($_GET['done'])) {
	if (isset($_POST['done_id'])) {
		callDone($_POST['done_id']);
	}
	header('Location: index.php');
}

if (isset($_GET['undo'])) {
	if (isset($_POST['undo_id'])) {
		callUndo($_POST['undo_id']);
	}
	header('Location: index.php');
}

if (getLogState()) {

	// set assignment ids
	$query = 'SELECT * FROM ' . DB_PREFIX . DB_USERS . ' WHERE 1';
	$user = queryMySQLData($query);
	$assignlist = array();

	while($row = $user->fetch_array()) {
		$assignlist[] = array($row['id'], $row['name']);
	}

	$context = array('user_name'      => getSingleUserData('name'),
					'user_id'         => getLogState(),
					'calls_undone'    => getCallArray(),
					'calls_done'      => getDoneCallArray(),
					'users_id'        => $assignlist,
					'done_path'       => 'index.php?done=True',
					'undo_path'       => 'index.php?undo=True',
					'form_path'       => 'index.php?newcall=True',
					'form_forename'   => 'forename',
					'form_lastname'   => 'lastname',
					'form_phone'      => 'phone',
					'form_subject'    => 'subject',
					'form_assignment' => 'assignments',
					'form_notes'      => 'notes');
	$page = 'home';
} else {
	$context = array(
		'form_path'          => 'index.php?login=True',
		'form_user_name'     => 'username',
		'form_password_name' => 'password',
		'form_keeplog_name'  => 'keeplog',
		'login_error'        => False
	);
	$page = 'login';
}

if ($page == 'home') {
	echo $twig->render('home.twig', $context);
} else if ($page == 'login') {
	echo $twig->render('login.twig', $context);
}
