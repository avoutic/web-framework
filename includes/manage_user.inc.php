<?php
require_once('base_logic.inc.php');
require_once('page_basic.inc.php');

class PageManageUser extends PageBasic
{
    static function get_filter()
    {
        return array(
                'user_id' => '\d+',
                'action' => 'delete_right|add_right',
                'right_id' => '\d+',
                'do' => 'yes'
                );
    }

    static function get_permissions()
    {
        return array(
                'logged_in',
                'user_management'
                );
    }

    function get_title()
    {
        return "Manage user";
    }

    function do_logic()
    {
        if (!strlen($this->state['input']['user_id']))
            die("Invalid input for user_id");

        $factory = new BaseFactory($this->database);

        // Retrieve user information
        //
        $user = $factory->get_user($this->state['input']['user_id'], 'UserBasic');

        // Check if this is a action attempt
        //
        if (strlen($this->state['input']['do']) && strlen($this->state['input']['action'])) {
            switch($this->state['input']['action']) {
                case 'delete_right':
                    $user->delete_right($this->state['input']['right_id']);
                    break;
                case 'add_right':
                    $user->add_right($this->state['input']['right_id']);
                    break;
            }
        }

        // Retrieve user information
        //
        $this->page_content['user']['user_id'] = $user->get_id();
        $this->page_content['user']['username'] = $user->username;
        $this->page_content['user']['name'] = $user->name;
        $this->page_content['user']['email'] = $user->email;
        $this->page_content['user']['verified'] = $user->verified;
        $this->page_content['user']['rights'] = array();

        $result_r = $this->database->Query('SELECT ur.id, r.name FROM rights AS r, user_rights AS ur WHERE r.id = ur.right_id AND ur.user_id = ?',
                array($this->state['input']['user_id']));

        if ($result_r->RecordCount() > 0) {
            foreach ($result_r as $k => $row)
                array_push($this->page_content['user']['rights'], array('id' => $row[0], 'name' => $row[1]));
        }

        $this->page_content['rights'] = array();

        $result_all_r = $this->database->Query('SELECT r.id, r.name FROM rights AS r', array());

        if ($result_all_r->RecordCount() > 0) {
            foreach ($result_all_r as $k => $row)
                array_push($this->page_content['rights'], array('id' => $row[0], 'name' => $row[1]));
        }
    }

    function display_content()
    {
?>
<div>
<h2>Information</h2>
<table>
  <tbody>
    <tr>
      <td>Username</td>
      <td><?=$this->page_content['user']['username']?></td>
    </tr>
    <tr>
      <td>Name</td>
      <td><?=$this->page_content['user']['name']?></td>
    </tr>
    <tr>
      <td>E-mail</td>
      <td><?=$this->page_content['user']['email']?></td>
    </tr>
    <tr>
      <td>Verified</td>
      <td><?=$this->page_content['user']['verified']?></td>
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
        foreach($this->page_content['user']['rights'] as $right) {
    	    print("<tr>\n");
	        print("  <td>".$right['name']."</td>\n");
	        print("  <td><a href=\"/manage_user?user_id=".$this->page_content['user']['user_id']."&amp;action=delete_right&amp;right_id=".$right['id']."&amp;do=yes\">Delete</a></td>\n");
    	    print("</tr>\n");
        }
?>
  </tbody>
</table>

<form class="contactform" action="/manage_user" method="post">
<fieldset>
  <input type="hidden" name="user_id" value="<?=$this->page_content['user']['user_id']?>" />
  <input type="hidden" name="action" value="add_right" />
  <input type="hidden" name="do" value="yes" />
  <legend>Add right</legend>
  <p>
    <label class="left" for="right_id">Right</label>
    <select name="right_id">
<?
        foreach ($this->page_content['rights'] as $right)
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
};
?>
