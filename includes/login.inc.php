<?php
function get_page_filter()
{
	return array(
		'return' => FORMAT_RETURN_PAGE,
		'username' => FORMAT_USERNAME,
		'password' => FORMAT_PASSWORD,
		'do' => 'yes'
	);
}

function get_page_permissions()
{
	return array();
}

function get_page_title()
{
	return "Login";
}

function do_page_logic()
{
	global $state, $database;

	// Check if this is a login attempt
	//
	if (!strlen($state['input']['do']))
		return;

	if (!strlen($state['input']['return']))
		$state['input']['return'] = DEFAULT_LOGIN_RETURN;

	// Check if username and password are present
	//
	if (!strlen($state['input']['username'])) {
		set_message('error', 'Please enter a correct username.', 'Usernames can contain letters, digits, dots, hyphens and underscores.');
		return;
	}
	if (!strlen($state['input']['password'])) {
		set_message('error', 'Please enter a password.', 'Passwords can contain any printable character.');
		return;
	}

	// Log in user
	//
	$result = $database->Query('SELECT id, password, name, verified, email FROM users WHERE username = ?',
		array($state['input']['username']));

	if ($result->RecordCount() > 1)
		die("Too many results for username $username! Exitting!");
	
	$success = false;
	if ($result->RecordCount() == 0 || $result->fields[1] != $state['input']['password']) {
		set_message('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
		return;
	}

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
	$_SESSION['username'] = $state['input']['username'];
	$_SESSION['name'] = $result->fields[2];
	$_SESSION['permissions'] = array('logged_in');
	$_SESSION['email'] = $result->fields[4];

	// Add permissions
	//
	$result_p = $database->Query('SELECT r.short_name FROM rights AS r, user_rights AS ur WHERE r.id = ur.right_id AND ur.user_id = ?',
		array($_SESSION['user_id']));
	foreach($result_p as $k => $row)
		array_push($_SESSION['permissions'], $row[0]);

	header("Location: ?mtype=success&message=".urlencode('Login successful.')."&".$state['input']['return']);
}

function display_header()
{
?>
<script src="sha1.js" type="text/javascript"></script>
<?
}

function display_page()
{
	global $config, $state;
?>
<form method="post" class="login_form" action="?page=login" enctype="multipart/form-data" onsubmit="password.value = hex_sha1(password.value); return true;">
	<fieldset class="login">
		<input type="hidden" name="do" value="yes"/>
		<input type="hidden" name="return" value="<?=$state['input']['return']?>"/>
		<legend>Login form</legend>
		<p>
			<label class="left" for="username">Username</label> <input type="text" class="field" id="username" name="username" value="<?=$state['input']['username']?>"/>
		</p>
		<p>
			<label class="left" for="password">Password</label> <input type="password" class="field" id="password" name="password"/>
		</p>
		<div>
			<label class="left">&nbsp;</label> <input type="submit" class="button" id="submit" value="Submit" />
		</div>
	</fieldset>
</form>
<span class="login_form_links"><a class="login_forgot_password_link" href="?page=forgot_password">Forgot your password?</a><? if ($config['allow_registration']) echo "| <a class=\"login_register_link\" href=\"?page=register_account\">No account yet?</a>";?></span>
<?
}
?>
