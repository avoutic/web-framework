<?php
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

	// Update and unverify account
	//
    if (FALSE === $database->Query('UPDATE users SET email = ?, verified = 0 WHERE id = ?',
                array($state['input']['email'],
                    $state['user_id'])))
    {
        die("Failed to update data! Exiting!");
    }

    $_SESSION['logged_in'] = false;
    $_SESSION['user_id'] = "";
    $_SESSION['permissions'] = array();

    session_destroy();

    // Redirect to verification request screen
    //
    header("Location: ?page=verify&username=".$state['username']);
}

function display_header()
{
}

function display_page()
{
	global $state;
?>
<form method="post" class="contactform" action="?page=change_email" enctype="multipart/form-data">
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
