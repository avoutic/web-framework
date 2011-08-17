<?php
require_once('page_basic.inc.php');

class PageUserOverview extends PageBasic
{
    static function get_permissions()
    {
        return array(
                'logged_in',
                'user_management'
                );
    }

    function get_title()
    {
        return "User overview";
    }

    function do_logic()
    {
        // Retrieve users
        //
        $result = $this->database->Query('SELECT id, username, name, email FROM users ORDER BY username', array());

        $this->page_content['user_info'] = array();

        foreach ($result as $k => $row) {
            array_push($this->page_content['user_info'], array('id' => $row[0], 'username' => $row[1], 'name' => $row[2], 'email' => $row[3]));
        }
    }

    function display_content()
    {
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
        foreach ($this->page_content['user_info'] as $users) {
        	print("<tr>\n");
            print("  <td><a href=\"/manage_user?user_id=".$users['id']."\">".$users['username']."</a></td>\n");
            print("  <td>".$users['name']."</td>\n");
            print("  <td>".$users['email']."</td>\n");
            print("  <td><a href=\"/grab_identity?user_id=".$users['id']."\">Grab ".$users['username']."</a></td>\n");
            print("</tr>\n");
        }
?>
  </tbody>
</table>
<?
    }
};
?>
