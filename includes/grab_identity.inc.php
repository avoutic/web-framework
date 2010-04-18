<?php
require_once('base_logic.inc.php');

function get_page_filter()
{
	return array(
		'user_id' => '\d+'
	);
}

function get_page_permissions()
{
	return array(
        'logged_in',
        'user_management',
        'grab_identity'
    );
}

function get_page_title()
{
	return "Grab identity";
}

function do_page_logic()
{
	global $state, $database;

	// Check if this is a login attempt
	//
	if (!strlen($state['input']['user_id']))
		return;

    $factory = new BaseFactory($database);

	// Log in user
	//
    $user = $factory->get_user($state['input']['user_id'], 'UserBasic');

	// Check if verified
	//
	if ($user->verified == 0) {
		set_message('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link. If you have not received such a mail, you can <a href="/send_verify?username='.$user->username.'">request a new one</a>.');
		return;
	}

	// Log in user
	//
	$success = true;

	$_SESSION['logged_in'] = true;
	$_SESSION['user_id'] = $user->get_id();
	$_SESSION['username'] = $user->username;
	$_SESSION['name'] = $user->name;
	$_SESSION['permissions'] = array('logged_in');
	$_SESSION['email'] = $user->email;

	// Add permissions
	//
	$result_p = $database->Query('SELECT r.short_name FROM rights AS r, user_rights AS ur WHERE r.id = ur.right_id AND ur.user_id = ?',
		array($_SESSION['user_id']));
	foreach($result_p as $k => $row)
		array_push($_SESSION['permissions'], $row[0]);

	header("Location: /?mtype=success&message=".urlencode('Login successful.'));
}

function display_header()
{
}

function display_page()
{
}
?>
