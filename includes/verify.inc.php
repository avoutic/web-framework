<?php
function get_page_filter()
{
	return array(
		'username' => FORMAT_USERNAME,
		'code' => FORMAT_VERIFY_CODE
	);
}

function get_page_permissions()
{
	return array();
}

function get_page_title()
{
	return "Request verification mail.";
}

function do_page_logic()
{
	global $state, $database;

	// Check if username is present
	//
	if (!strlen($state['input']['username']))
		return;

	// Check user status
	//
	$result = $database->Query('SELECT id, verified, email, name FROM users WHERE username = ?',
		array($state['input']['username']));

	if ($result->RecordCount() != 1)
		return;
	
	if ($result->fields[1] == 1)
		return;

	$hash = sha1("SuperSecretHashingKey*".$state['input']['username']."*".$result->fields[2]."*".$result->fields[0]."*".$result->fields[3]);

	if (strlen($state['input']['code'])) {
		if ($state['input']['code'] == $hash) {
			if (FALSE === $database->Query('UPDATE users SET verified=1 WHERE username=?',
				array($state['input']['username'])))
			{
				die('Failed to update verified status for user '.$state['input']['username'].'! Exiting!');
			}

			// Redirect to main sceen
			//
			header("Location: ?mtype=success&message=".urlencode("Verification succeeded")."&extra_message=".urlencode("Verification succeeded. You can now use your account."));
		}
		return;
	}

	// Send mail to user
	//
	mail($result->fields[2], SITE_NAME." account verification mail",
		"Welcome ".$result->fields[3].",\n\n".
		"You successfully created your account for ".SITE_NAME.". In order to verify the account, please go to the following web location by either clicking the link or manually entering the address into your webbrowser.\n\n".
		"To verify the account go to: http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI'])."/verify?username=".$state['input']['username']."&code=$hash.\n\n".
		"Best regards,\n".
		MAIL_FOOTER,
		"From: ".MAIL_ADDRESS."\r\n");
	
	// Redirect to main sceen
	//
	header("Location: ?mtype=success&message=".urlencode("Verification mail sent")."&extra_message=".urlencode("Verification mail is sent. Please check your mailbox and follow the instructions."));
}

function display_header()
{
?>
<?
}

function display_page()
{
?>
No content.
<?
}
?>
