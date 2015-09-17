<?php

header('Content-Type: text/html; charset=utf-8');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/bakir-anrufliste-config.php');
require(__DIR__ . '/calls.php');


// init twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);
$slack = new Maknz\Slack\Client(SLACK_WEBHOOK, ['link_names' => true]);
$context = array();

// init php-components
initLogSys();

// add custom column for fullname
addCustomFields(['fullname' => 'VARCHAR(100)']);

// init general variables
$page = 'login';
// meaning of error variables
// 0 - everything is ok
// 1 - internal error
// 2 - empty field
// 3 - unallowed field entry
// 4 - something different, specified behind the initialisation
$login_error = 0;
$password_reset_error = 0; // 4 - password_reset_new and password_reset_repeat doeas not match
$call_new_error = 0;
$call_done_error = 0;
$call_undo_error = 0;
$call_delete_error = 0;

$fields_login = array(
	'username' => '',
);
$fields_call_new = array(
	'forename'    => '',
	'lastname'    => '',
	'phone'       => '',
	'subject'     => '',
	'notes'       => '',
	'assignments' => [],
);

// calls which should be shown
$view = 'calls_undone';

// check whether a variable is set, if so return it else return ''
function setPostVar($var) {
	if (!empty($_POST[$var])) {
		return $_POST[$var];
	} else {
		return '';
	}
}

if (getLogState()) {

	// log out
	if (isset($_POST['logout'])) {
		logUserOut();
		header('Location: index.php?site=login');
	}
	// change password
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
		} else {
			$password_reset_error = 2;
		}
	}

	// new call
	if (isset($_POST['call_new'])) {

		$set_call_new_fields = false;

		if (
			!empty($_POST['call_new_forename']) &&
			!empty($_POST['call_new_lastname']) &&
			!empty($_POST['call_new_phone']) &&
			!empty($_POST['call_new_subject'] &&
			!empty($_POST['call_new_assignments']))
		) {
			$call_new_result = newCall($_POST['call_new_forename'], $_POST['call_new_lastname'], $_POST['call_new_phone'], $_POST['call_new_subject'], $_POST['call_new_notes'], $_POST['call_new_assignments']);

			if ($call_new_result) {

				function assignedNames() {
					$id_query = '';

					foreach($_POST['call_new_assignments'] as $assignment) {
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

				if (SLACK_WEBHOOK) {
					$slack->send($message);
				}

				header('Location: index.php?site=home&view=calls_undone');
			} else {
				$call_new_error = 1;
				$set_call_new_fields = true;
			}
		} else {
			$call_new_error = 2;
			$set_call_new_fields = true;
		}

		if ($set_call_new_fields) {
			$fields_call_new['forename']    = setPostVar('call_new_forename');
			$fields_call_new['lastname']    = setPostVar('call_new_lastname');
			$fields_call_new['phone']       = setPostVar('call_new_phone');
			$fields_call_new['subject']     = setPostVar('call_new_subject');
			$fields_call_new['notes']       = setPostVar('call_new_notes');
			$fields_call_new['assignments'] = setPostVar('call_new_assignments');
		}

	}

	// call done
	if (isset($_POST['call_done'])) {
		if (isset($_POST['call_done_id'])) {
			$call_done_result = callDone($_POST['call_done_id']);
			if ($call_done_result) {
				header('Location: index.php?site=home&view=calls_done');
			} else {
				$call_done_error = 1;
			}
		}
	}

	// undo call
	if (isset($_POST['call_undo'])) {
		if (isset($_POST['call_undo_id'])) {
			$call_undo_result = callUndo($_POST['call_undo_id']);
			if ($call_undo_result) {
				$call_undo_error = 0;
				header('Location: index.php?site=home&view=calls_undone');
			} else {
				$call_undo_error = 1;
			}
		}
	}

	// delete call
	if (isset($_POST['call_delete'])) {
		if (isset($_POST['call_delete_id'])) {
			$call_delete_result = callDelete($_POST['call_delete_id']);
			if ($call_delete_result) {
				$call_delete_error = 0;
				header('Location: index.php?site=home');
			} else {
				$call_delete_error = 1;
			}
		}
	}

} else {
	// log in
	if (isset($_POST['login'])) {
		if (!empty($_POST['login_name']) && !empty($_POST['login_password'])) {
			if (isset($_POST['login_keeplog'])) {
				$login_result = logUserIn($_POST['login_name'], $_POST['login_password'], true);
			} else {
				$login_result = logUserIn($_POST['login_name'], $_POST['login_password'], false);
			}

			if ($login_result) {
				$login_error = 0;
				header('Location: index.php?site=home');
			}
			else {
				$fields_login['username'] = setPostVar('login_name');
				$login_error = 1;
			}

		} else {
			$fields_login['username'] = setPostVar('login_name');
			$login_error = 2;
		}
	}

}

// site management
if (isset($_GET['site'])) {

	// login?
	if ($_GET['site'] == 'login') {

		// logged in?
		if (getLogState()) {
			header('Location: index.php?site=home');

		// not logged in?
		} else {

			$context = array(
				'f_home_path'      => 'index.php?site=home',
				'f_login_submit'   => 'login',
				'f_login_user'     => 'login_name',
				'f_login_password' => 'login_password',
				'f_login_keeplog'  => 'login_keeplog',
				'f_fields_login'   => $fields_login,
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
					'id'        =>  $row['id'],
					'username'  =>  $row['name'],
					'fullname'  =>  $row['fullname']
				);
			}

			if (isset($_GET['view'])) {
				if ($_GET['view'] == 'calls_undone') { $view = 'calls_undone'; }
				if ($_GET['view'] == 'calls_done') { $view = 'calls_done'; }
			}

			$context = array(
				'user_name'               => getSingleUserData('name'),
				'user_id'                 => getLogState(),
				'view'                    => $view,
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
				'f_call_delete_submit'    => 'call_delete',
				'f_call_delete_id'        => 'call_delete_id',
				'f_call_new_forename'     => 'call_new_forename',
				'f_call_new_lastname'     => 'call_new_lastname',
				'f_call_new_phone'        => 'call_new_phone',
				'f_call_new_subject'      => 'call_new_subject',
				'f_call_new_assignments'  => 'call_new_assignments',
				'f_call_new_notes'        => 'call_new_notes',
				'f_call_new_submit'       => 'call_new',
				'f_fields_call_new'       => $fields_call_new,
				'call_new_error'          => $call_new_error,
				'call_done_error'         => $call_done_error,
				'call_undo_error'         => $call_undo_error,
				'call_delete_error'       => $call_delete_error
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

if ($page == 'home') {
	echo $twig->render('home.twig', $context);
} else if ($page == 'login') {
	echo $twig->render('login.twig', $context);
} else if ($page == 'account') {
	echo $twig->render('account.twig', $context);
}
