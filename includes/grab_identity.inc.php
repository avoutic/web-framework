<?php
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

	// Log in user
	//
	$result = $database->Query('SELECT id, username, name, verified, email FROM users WHERE id = ?',
		array($state['input']['user_id']));

	if ($result->RecordCount() != 1)
		die("No correct results for username $user_id! Exitting!");

	// Check if verified
	//
	if ($result->fields[3] == 0) {
		set_message('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link. If you have not received such a mail, you can <a href="?page=verify&username='.$state['input']['username'].'">request a new one</a>.');
		return;
	}

	// Log in user
	//
	$success = true;

	$_SESSION['logged_in'] = true;
	$_SESSION['user_id'] = $result->fields[0];
	$_SESSION['username'] = $result->fields[1];
	$_SESSION['name'] = $result->fields[2];
	$_SESSION['permissions'] = array('logged_in');
	$_SESSION['email'] = $result->fields[4];

	// Add permissions
	//
	$result_p = $database->Query('SELECT r.short_name FROM rights AS r, user_rights AS ur WHERE r.id = ur.right_id AND ur.user_id = ?',
		array($_SESSION['user_id']));
	foreach($result_p as $k => $row)
		array_push($_SESSION['permissions'], $row[0]);

	header("Location: ?mtype=success&message=".urlencode('Login successful.'));
}

function display_header()
{
}

function display_page()
{
}
?>
