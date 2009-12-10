<?php
function get_page_filter()
{
	return array(
		'return' => FORMAT_RETURN_PAGE,
	);
}

function get_page_permissions()
{
	return array();
}

function get_page_title()
{
	return "Logoff";
}

function do_page_logic()
{
	global $state;

	$_SESSION['logged_in'] = false;
	$_SESSION['user_id'] = "";
	$_SESSION['permissions'] = array();

	session_destroy();

	header("Location: ?".$state['input']['return']);
}

function display_header()
{
}

function display_page()
{
	global $state;
?>
<div>
Logging off.
</div>
<?
}
?>
