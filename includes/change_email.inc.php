<?php
require_once('base_logic.inc.php');

function get_page_filter()
{
	return array(
		'email' => FORMAT_EMAIL,
		'do' => 'yes'
	);
}

function get_page_permissions()
{
	return array(
		'logged_in'
		);
}

function get_page_title()
{
	return "Change email address";
}

function do_page_logic()
{
	global $state, $database;

	// Check if this is a true attempt
	//
	if (!strlen($state['input']['do']))
		return;

	// Check if email address is present
	//
	if (!strlen($state['input']['email'])) {
        set_message('error', 'Please enter a correct Email address.', 'Email addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');
        return;
    }

	// Change email
	//
    $factory = new BaseFactory($database);
    $user = $factory->get_user($state['user_id'], 'UserBasic');

    $result = $user->change_email($state['input']['email']);

    if ($result == User::ERR_DUPLICATE_EMAIL)
    {
        set_message('error', 'E-mail address is alreay in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.');
        return;
    }
    if ($result != User::RESULT_SUCCESS)
    {
        set_message('error', 'Unknown errorcode: \''.$result."'", "Please inform the administrator.");
        return;
    }

    // Logout user
    //
    $_SESSION['logged_in'] = false;
    $_SESSION['user_id'] = "";
    $_SESSION['permissions'] = array();

    session_destroy();

    // Send verification mail
    //
    $user->send_verify_mail();

    // Redirect to verification request screen
    //
    header('Location: /?mtype=success&message='.urlencode('Verification mail has been sent.').'&extra_message='.urlencode('The verification mail has been sent. Please wait for the e-mail in your inbox and follow the instructions.'));
    exit();
}

function display_header()
{
}

function display_page()
{
	global $state;
?>
<form method="post" class="contactform" action="/change_email" enctype="multipart/form-data">
	<fieldset class="register">
		<input type="hidden" name="do" value="yes"/>
		<legend>Change email</legend>
        <p>
            <label class="left" for="email">E-mail</label> <input type="text" class="field" id="email" name="email" value="<?=$state['input']['email']?>"/>
        </p>
	</fieldset>
	<p>
		<label>&nbsp;</label> <input type="submit" class="button" id="submit" value="Change" />
	</p>
</form>
<?
}
?>
