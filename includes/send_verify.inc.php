<?php
require_once('base_logic.inc.php');

function get_page_filter()
{
	return array(
		'username' => FORMAT_USERNAME,
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

    $factory = new BaseFactory($database);

	// Check user status
	//
    $user = $factory->get_user_by_username($state['input']['username'], 'UserBasic');
	
	if ($user->verified == 1)
    {
        header('Location: /?mtype=success&message='.urlencode('User already verified.'));
        exit();
    }

    $user->send_verify_mail();

	// Redirect to main sceen
	//
	header("Location: /?mtype=success&message=".urlencode("Verification mail sent")."&extra_message=".urlencode("Verification mail is sent. Please check your mailbox and follow the instructions."));
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
