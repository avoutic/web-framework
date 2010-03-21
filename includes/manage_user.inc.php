<?php
function get_page_filter()
{
	return array(
		'user_id' => '\d+',
		'action' => 'delete_right|add_right',
		'right_id' => '\d+',
		'do' => 'yes'
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
	return "Manage user";
}

function do_page_logic()
{
	global $state, $database, $page_content;

	if (!strlen($state['input']['user_id']))
		die("Invalid input for user_id");

	// Check if this is a action attempt
	//
	if (strlen($state['input']['do']) && strlen($state['input']['action'])) {
		switch($state['input']['action']) {
		case 'delete_right':
			$database->Query('DELETE FROM user_rights WHERE id = ?',
					array(
						$state['input']['right_id']
					));
			break;
		case 'add_right':
			$database->Query('INSERT INTO user_rights (user_id, right_id) VALUES (?, ?)',
					array(
						$state['input']['user_id'],
						$state['input']['right_id']
					));
			break;
		}
	}

	// Retrieve user information
	//
	$result = $database->Query('SELECT id, username, name, email, verified FROM users WHERE id = ?',
			array($state['input']['user_id']));

	if ($result->RecordCount() != 1)
		die("Incorrect number of results for username $username! Exiting!");

	$page_content['user']['user_id'] = $result->fields[0];
	$page_content['user']['username'] = $result->fields[1];
	$page_content['user']['name'] = $result->fields[2];
	$page_content['user']['email'] = $result->fields[3];
	$page_content['user']['verified'] = $result->fields[4];
	$page_content['user']['rights'] = array();

	$result_r = $database->Query('SELECT ur.id, r.name FROM rights AS r, user_rights AS ur WHERE r.id = ur.right_id AND ur.user_id = ?',
			array($state['input']['user_id']));

	if ($result_r->RecordCount() > 0) {
		foreach ($result_r as $k => $row)
			array_push($page_content['user']['rights'], array('id' => $row[0], 'name' => $row[1]));
	}

	$page_content['rights'] = array();

	$result_all_r = $database->Query('SELECT r.id, r.name FROM rights AS r', array());

	if ($result_all_r->RecordCount() > 0) {
		foreach ($result_all_r as $k => $row)
			array_push($page_content['rights'], array('id' => $row[0], 'name' => $row[1]));
	}
}

function display_header()
{
}

function display_page()
{
	global $page_content;
?>
<div>
<h2>Information</h2>
<table>
  <tbody>
    <tr>
      <td>Username</td>
      <td><?=$page_content['user']['username']?></td>
    </tr>
    <tr>
      <td>Name</td>
      <td><?=$page_content['user']['name']?></td>
    </tr>
    <tr>
      <td>E-mail</td>
      <td><?=$page_content['user']['email']?></td>
    </tr>
    <tr>
      <td>Verified</td>
      <td><?=$page_content['user']['verified']?></td>
    </tr>
  </tbody>
</table>

<h2>Rights</h2>
<table>
  <thead>
    <tr>
      <th scope="col">Name</th>
      <th scope="col">Delete</th>
    </tr>
  </thead>
  <tbody>
<?
foreach($page_content['user']['rights'] as $right) {
	print("<tr>\n");
	print("  <td>".$right['name']."</td>\n");
	print("  <td><a href=\"/manage_user?user_id=".$page_content['user']['user_id']."&amp;action=delete_right&amp;right_id=".$right['id']."&amp;do=yes\">Delete</a></td>\n");
	print("</tr>\n");
}
?>
  </tbody>
</table>

<form class="contactform" action="/manage_user" method="post">
<fieldset>
  <input type="hidden" name="user_id" value="<?=$page_content['user']['user_id']?>" />
  <input type="hidden" name="action" value="add_right" />
  <input type="hidden" name="do" value="yes" />
  <legend>Add right</legend>
  <p>
    <label class="left" for="right_id">Right</label>
    <select name="right_id">
<?
foreach ($page_content['rights'] as $right)
	print("  <option value=\"".$right['id']."\">".$right['name']."</option>\n");
?>
    </select>
  </p>
  <div>
    <label>&nbsp;</label> <input type="submit" class="button" value="Add right" />
  </div>
</fieldset>
</form>
</div>
<?
}
?>
