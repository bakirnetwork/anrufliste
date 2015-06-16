<?php

header('Content-Type: text/html; charset=utf-8');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/bakir-anrufliste-config.php');
require(__DIR__ . '/calls.php');


// init twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);
$context = array();

// init php-components
initLogSys();

// init general variables
$page = 'login';
// meaning of error variables
// 0     - everything is ok
// 1     - internal error
// 2     - empty field 
// 3     - unallowed field entry
// 4-... - something different, specified behind the initialisation
$login_error = 0;
$password_reset_error = 0; // 4 - password_reset_new and password_reset_repeat doeas not match
$call_new_error = 0;
$call_done_error = 0;
$call_undo_error = 0;


if (getLogState()) {
	
	// Abmelden
	if (isset($_POST['logout'])) {
		logUserOut();
		header('Location: index.php?site=login');
	}

	// Passwort ändern
	if (isset($_POST['password_reset'])) {
		if (
			isset($_POST['password_reset_old']) && 
			isset($_POST['password_reset_new']) &&
			isset($_POST['password_reset_repeat'])
		) {	
			if ($_POST['password_reset_new'] == $_POST['password_reset_repeat']) {
				$password_reset_result = resetPassword($_POST['password_reset_old'], $_POST['password_reset_new']);
				if ($password_reset_result) {
					header('Location: index.php?site=home'); 
				} else { 
					$password_reset_error = 1; 
				}
			} else {
				$password_reset_error = 4;	
			}

		} else { $password_reset_error = 2; }	
	}

	// Neuer Anruf
	if (isset($_POST['call_new'])) {
		if (
			isset($_POST['call_new_forename']) &&
			isset($_POST['call_new_lastname']) &&
			isset($_POST['call_new_phone']) &&
			isset($_POST['call_new_subject'])
		) {
			$call_new_result = newCall($_POST['call_new_forename'], $_POST['call_new_lastname'], $_POST['call_new_phone'], $_POST['call_new_subject'], $_POST['call_new_notes'], $_POST['call_new_assignments']);

			if ($call_new_result) {
				header('Location: index.php?site=home');		
			} else {
				$call_new_error = 1;	
			}
		} else {
			$call_new_error = 2;	
		}

	}

	// Anruf fertig bearbeitet
	if (isset($_POST['call_done'])) {
		if (isset($_POST['call_done_id'])) {
			$call_done_result = callDone($_POST['call_done_id']);
			if ($call_done_result) {
				header('Location: index.php?site=home');	
			} else {
				$call_done_error = 1;	
			}
		}	
	}

	// Anruf zurücksetzen
	if (isset($_POST['call_undo'])) {
		if (isset($_POST['call_undo_id'])) {
			$call_undo_result = callUndo($_POST['call_undo_id']);
			if ($call_undo_result) {
				header('Location: index.php?site=home');	
			} else {
				$call_done_error = 1;	
			}
		}
	}

} else {
	// Anmelden
	if (isset($_POST['login'])) {
		if (isset($_POST['login_name']) && isset($_POST['login_password'])) {
			if (isset($_POST['login_keeplog'])) {
				$login_result = logUserIn($_POST['login_name'], $_POST['login_password'], true);
			} else {
				$login_result = logUserIn($_POST['login_name'], $_POST['login_password'], false);
			}

			if ($login_result) { 
				header('Location: index.php?site=home'); 
			}
			else { 
				$login_error = 1; 
			}

		} else { $login_error = 2; }	
	}

}


// Seiten-Management
if (isset($_GET['site'])) {
	
	// login?
	if ($_GET['site'] == 'login') {
		
		// angemeldet?
		if (getLogState()) {
			header('Location: index.php?site=home');
		
		// nicht angemeldet?
		} else {
			
			$context = array(
				'f_home_path'      => 'index.php?site=home',
				'f_login_submit'   => 'login',
				'f_login_user'     => 'login_name',
				'f_login_password' => 'login_password',
				'f_login_keeplog'  => 'login_keeplog',
				'login_error'      => $login_error
			);
			$page = 'login';		
		}
		
	// account?
	} elseif ($_GET['site'] == 'account') {
		if (getLogState()) {
			$context = array(
				'f_account_path'          => 'index.php?site=account',
				'f_home_path'             => 'index.php?site=home',
				'f_password_reset_old'    => 'password_reset_old',
				'f_password_reset_new'    => 'password_reset_new',
				'f_password_reset_repeat' => 'password_reset_repeat',
				'f_password_reset_submit' => 'password_reset',
				'password_reset_error'    => $password_reset_error
			);
			$page = 'account';
		} else {
			header('Location: index.php?site=login');		
		}
		
	// home?
	} elseif ($_GET['site'] == 'home') {
		if (getLogState()) {

			$query = 'SELECT * FROM ' . DB_PREFIX . DB_USERS . ' WHERE 1';
			$userList = queryMySQLData($query);
			$users = array();
	
			while($row = $userList->fetch_array()) {
				$users[] = array(
					'id'   => $row['id'],
					'name' => $row['name']
				);
			}
	
			$context = array(
				'user_name'               => getSingleUserData('name'),
				'user_id'                 => getLogState(),
				'calls_undone'            => getCallArray(),
				'calls_done'              => getDoneCallArray(),
				'users'                   => $users,
				'f_home_path'             => 'index.php?site=home',
				'f_account_path'          => 'index.php?site=account',
				'f_logout_submit'         => 'logout',
				'f_call_done_submit'      => 'call_done',
				'f_call_done_id'          => 'call_done_id',
				'f_call_undo_submit'      => 'call_undo',
				'f_call_undo_id'          => 'call_undo_id',
				'f_call_new_forename'     => 'call_new_forename',
				'f_call_new_lastname'     => 'call_new_lastname',
				'f_call_new_phone'        => 'call_new_phone',
				'f_call_new_subject'      => 'call_new_subject',
				'f_call_new_assignments'  => 'call_new_assignments',
				'f_call_new_notes'        => 'call_new_notes',
				'f_call_new_submit'       => 'call_new',
				'call_new_error'          => $call_new_error,
				'call_done_error'         => $call_done_error,
				'call_undo_error'         => $call_undo_error
			);
	
			$page = 'home';	
		} else {
			header('Location: index.php?site=login');		
		}
	} else {
		header('Location: index.php?site=login');		
	}
	
} else {
	header('Location: index.php?site=home');
}

/*
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
		'login_error'        => false
	);
	$page = 'login';
} */

if ($page == 'home') {
	echo $twig->render('home.twig', $context);
} else if ($page == 'login') {
	echo $twig->render('login.twig', $context);
} else if ($page == 'account') {
	echo $twig->render('account.twig', $context);
}
