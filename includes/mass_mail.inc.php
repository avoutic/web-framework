<?php
function get_page_filter()
{
	return array(
		'query' => '.*',
		'address' => '.*',
		'title' => '.*',
		'text' => '.*',
		'do' => 'yes|preview'
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
	return "Mass mail";
}

function do_page_logic()
{
	global $state, $page_content, $database;

	$page_content['user_info'] = array();

	// Retrieve users
	//
	$result = $database->Query('SELECT u.id, u.username, u.name, u.email FROM users AS u '.$state['input']['query'], array());
	
	foreach ($result as $k => $row) {
		array_push($page_content['user_info'], array('id' => $row[0], 'username' => $row[1], 'name' => $row[2], 'email' => $row[3]));
	}

	// Check if page logic is needed
	//
	if (!strlen($state['input']['do']))
		return;

	if ($state['input']['do'] == 'yes') {
		if (!strlen($state['input']['title']) ||
			!strlen($state['input']['text']) ||
			!strlen($state['input']['address']))
		{
			set_message('error', 'One of the input values was empty.', '');
			return;
		}

		foreach ($page_content['user_info'] as $users) {
			$text = $state['input']['text'];
			$text = preg_replace('/USERNAME/is', $users['username'], $text);
			$text = preg_replace('/NAME/is', $users['name'], $text);
			$text = preg_replace('/EMAIL/is', $users['email'], $text);

			mail($users['email'],
				SITE_NAME.": ".$state['input']['title'],
				$text,
				"From: ".$state['input']['address']."\r\n");
		}

		set_message('success', 'Mass mail sent', '');
	}
}

function display_header()
{
}

function display_page()
{
	global $state, $page_content;
?>
<form class="contactform" action="/mass_mail" method="post">
  <fieldset>
    <input type="hidden" name="do" value="preview"/>
    <legend>Selection</legend>
    <p>
      <label class="left" for="query">Query</label>
      <input type="text" class="field" id="query" name="query" value="<?=$state['input']['query']?>"/>
    </p>
    <legend>Message</legend>
    <p>
      <label class="left" for="address">Mail address</label>
      <input type="text" class="field" id="address" name="address" value="<?=$state['input']['address']?>"/>
    </p>
    <p>
      <label class="left" for="title">Title</label>
      <input type="text" class="field" id="title" name="title" value="<?=$state['input']['title']?>"/>
    </p>
    <p>
      <label class="left" for="text">Text</label>
      <textarea cols="50" rows="20" class="field" id="text" name="text"><?=$state['input']['text']?></textarea>
    </p>
    <div>
      <label class="left">&nbsp;</label>
      <input type="submit" class="button" id="submit" value="Preview"/>
    </div>
  </fieldset>
</form>
<p><b>Query:</b><br/>
SELECT u.id, u.username, u.name, u.email FROM users AS u <?=$state['input']['query']?>
</p>
<table>
  <thead>
    <tr>
      <th scope="col">Id</th>
      <th scope="col">Username</th>
      <th scope="col">Name</th>
      <th scope="col">E-mail</th>
    </tr>
  </thead>
  <tbody>
<?
if (count($page_content['user_info']) == 0) {
	print("<tr><td colspan=\"4\">No selection</td></tr>\n");
}

foreach ($page_content['user_info'] as $users) {
	print("<tr>\n");
	print("  <td>".$users['id']."</td>\n");
	print("  <td>".$users['username']."</td>\n");
	print("  <td>".$users['name']."</td>\n");
	print("  <td>".$users['email']."</td>\n");
	print("</tr>\n");
}
?>
  </tbody>
</table>
<form class="contactform" action="/mass_mail" method="post">
  <fieldset>
    <input type="hidden" name="do" value="yes"/>
    <input type="hidden" name="address" value="<?=$state['input']['address']?>">
    <input type="hidden" name="title" value="<?=$state['input']['title']?>">
    <input type="hidden" name="text" value="<?=$state['input']['text']?>">
    <input type="hidden" name="query" value="<?=$state['input']['query']?>">
    <legend>Send mass mail</legend>
    <div>
      <label class="left">&nbsp;</label>
      <input type="submit" class="button" id="submit" value="Send"/>
    </div>
  </fieldset>
</form>
<?
}
?>
