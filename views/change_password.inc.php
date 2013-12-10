<?php
require_once($includes.'base_logic.inc.php');

class PageChangePassword extends PageBasic
{
    static function get_filter()
{
	return array(
		'orig_password' => FORMAT_PASSWORD,
		'password' => FORMAT_PASSWORD,
		'password2' => FORMAT_PASSWORD,
	);
}

static function get_permissions()
{
	return array(
		'logged_in'
		);
}

function get_title()
{
	return "Change password";
}

function do_logic()
{
	// Check if this is a true attempt
	//
	if (!strlen($this->state['input']['do']))
		return;

    $orig_password = $this->state['input']['orig_password'];
    $password = $this->state['input']['password'];
    $password2 = $this->state['input']['password2'];

    // Check if javascript is enabled
    //
    if (!strlen($password))
    {
        $this->add_message('error', 'Javascript is disabled.', 'Javascript is disabled or is not allowed. It is not possible to continue without Javascript.');
        return;
    }

	// Check if passwords are present
	//
	if (!strlen($orig_password) || $orig_password == EMPTY_PASSWORD_HASH_SHA1) {
		$this->add_message('error', 'Please enter original password.', 'Passwords can contain any printable character.');
		return;
	}

	if (!strlen($password) || $password == EMPTY_PASSWORD_HASH_SHA1) {
		$this->add_message('error', 'Please enter a password.', 'Passwords can contain any printable character.');
		return;
	}

	if (!strlen($password2) || $password2 == EMPTY_PASSWORD_HASH_SHA1) {
		$this->add_message('error', 'Please enter the password verification.', 'Password verification should match password.');
		return;
	}

	if ($password != $password2) {
		$this->add_message('error', 'Passwords don\'t match.', 'Password and password verification should be the same.');
		return;
	}

    $factory = new BaseFactory($this->global_info);
    $user = $factory->get_user($this->state['user_id']);
    $result = $user->change_password($orig_password, $password);

    if ($result == User::ERR_ORIG_PASSWORD_MISMATCH)
    {
		$this->add_message('error', 'Original password is incorrect.', 'Please re-enter correct password.');
		return;
	}

    if ($result != User::RESULT_SUCCESS)
    {
        $this->add_message('error', 'Unknown errorcode: \''.$result."'", "Please inform the administrator.");
        return;
    }

	// Redirect to main sceen
	//
	header("Location: /?".add_message_to_url('success','Password changed successfully.'));
    exit();
}

function display_header()
{
?>
<script src="base/sha1.js" type="text/javascript"></script>
<?
}

function display_content()
{
    $this->load_template('change-password.tpl', $this->page_content);
}
};
?>
