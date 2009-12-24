<?php
function get_page_filter()
{
	return array(
	);
}

function get_page_permissions()
{
	return array(
		'logged_in',
		'user_management'
	);
}

function get_page_title()
{
	return "User overview";
}

function do_page_logic()
{
	global $page_content, $database;

	// Retrieve users
	//
	$result = $database->Query('SELECT id, username, name, email FROM users ORDER BY username', array());
	
	$page_content['user_info'] = array();

	foreach ($result as $k => $row) {
		array_push($page_content['user_info'], array('id' => $row[0], 'username' => $row[1], 'name' => $row[2], 'email' => $row[3]));
	}
}

function display_header()
{
}

function display_page()
{
	global $page_content;
?>
<table>
  <thead>
    <tr>
      <th scope="col">Username</th>
      <th scope="col">Name</th>
      <th scope="col">E-mail</th>
      <th scope="col">Grab</th>
    </tr>
  </thead>
  <tbody>
<?
foreach ($page_content['user_info'] as $users) {
	print("<tr>\n");
	print("  <td><a href=\"?page=manage_user&amp;user_id=".$users['id']."\">".$users['username']."</a></td>\n");
	print("  <td>".$users['name']."</td>\n");
	print("  <td>".$users['email']."</td>\n");
	print("  <td><a href=\"?page=grab_identity&amp;user_id=".$users['id']."\">Grab ".$users['username']."</a></td>\n");
	print("</tr>\n");
}
?>
  </tbody>
</table>
<?
}
?>
