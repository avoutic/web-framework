<?php
require_once('base_logic.inc.php');

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
	return "Verify mail address.";
}

function do_page_logic()
{
	global $state, $database;

	// Check if username is present
	//
	if (!strlen($state['input']['username']))
		return;

	// Check if code is present
	//
	if (!strlen($state['input']['code']))
		return;

    $factory = new BaseFactory($database);

	// Check user status
	//
	$result = $database->Query('SELECT id, verified, email, name FROM users WHERE username = ?',
		array($state['input']['username']));

	if ($result->RecordCount() != 1)
		return;
	
	if ($result->fields['verified'] == 1)
    {
        header('Location: /?mtype=success&message='.urlencode('User alread verified.'));
        exit();
    }

    $user = $factory->get_user($result->fields['id'], 'UserBasic');

    $hash = $user->generate_verify_code();

    if ($state['input']['code'] == $hash) {
        $user->set_verified();

        // Redirect to main sceen
        //
        header("Location: /".SITE_LOGIN_PAGE."?mtype=success&message=".urlencode("Verification succeeded")."&extra_message=".urlencode("Verification succeeded. You can now use your account."));
        exit();
    }
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
