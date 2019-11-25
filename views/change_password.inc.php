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
	if (!strlen($this->get_input_var('do')))
		return;

    $orig_password = $this->get_input_var('orig_password');
    $password = $this->get_input_var('password');
    $password2 = $this->get_input_var('password2');

	// Check if passwords are present
	//
	if (!strlen($orig_password))
    {
		$this->add_message('error', 'Please enter original password.', 'Passwords can contain any printable character.');
		return;
	}

	if (!strlen($password))
    {
		$this->add_message('error', 'Please enter a password.', 'Passwords can contain any printable character.');
		return;
	}

	if (!strlen($password2))
    {
		$this->add_message('error', 'Please enter the password verification.', 'Password verification should match password.');
		return;
	}

	if ($password != $password2)
    {
		$this->add_message('error', 'Passwords don\'t match.', 'Password and password verification should be the same.');
		return;
	}

    $base_factory = new BaseFactory($this->global_info);
    $user = $base_factory->get_user($this->state['user_id']);
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

    // Invalidate old sessions
    //
    $this->auth->invalidate_sessions($user->id);
    $this->auth->set_logged_in($user);

	// Redirect to main sceen
	//
	header("Location: /?".add_message_to_url('success','Password changed successfully.'));
    exit();
}

function display_content()
{
    $this->load_template('change-password.tpl', $this->page_content);
}
};
?>
