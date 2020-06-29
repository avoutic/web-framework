<?php
require_once($includes.'base_logic.inc.php');

class PageManageUser extends PageBasic
{
    static function get_filter()
    {
        return array(
                'user_id' => '\d+',
                'action' => 'delete_right|add_right',
                'right_name' => '\w+',
                );
    }

    static function get_permissions()
    {
        return array(
                'logged_in',
                'user_management',
                );
    }

    function get_title()
    {
        return "Manage user";
    }

    function do_logic()
    {
        $user_id = $this->get_input_var($user_id, true);

        $base_factory = new BaseFactory();

        // Retrieve user information
        //
        $user = $base_factory->get_user($user_id);

        // Check if this is a action attempt
        //
        $action = $this->get_input_var('action');
        if (strlen($this->get_input_var('do')) && strlen($action))
        {
            switch($action)
            {
                case 'delete_right':
                    $user->delete_right($this->get_input_var('right_name'));
                    break;
                case 'add_right':
                    $user->add_right($this->get_input_var('right_name'));
                    break;
            }
        }

        // Retrieve user information
        //
        $this->page_content['user']['user_id'] = $user->id;
        $this->page_content['user']['username'] = $user->username;
        $this->page_content['user']['name'] = $user->name;
        $this->page_content['user']['email'] = $user->email;
        $this->page_content['user']['verified'] = $user->is_verified();
        $this->page_content['user']['rights'] = array();

        $result_r = $this->query('SELECT ur.id, r.short_name, r.name FROM rights AS r, user_rights AS ur WHERE r.id = ur.right_id AND ur.user_id = ?',
                array($user_id));

        if ($result_r->RecordCount() > 0) {
            foreach ($result_r as $k => $row)
                array_push($this->page_content['user']['rights'], array('id' => $row['id'], 'short_name' => $row['short_name'], 'name' => $row['name']));
        }

        $this->page_content['rights'] = array();

        $result_all_r = $this->query('SELECT r.id, r.short_name, r.name FROM rights AS r', array());

        if ($result_all_r->RecordCount() > 0) {
            foreach ($result_all_r as $k => $row)
                array_push($this->page_content['rights'], array('id' => $row['id'], 'short_name' => $row['short_name'], 'name' => $row['name']));
        }
    }

    function display_content()
    {
        $this->load_template('manage_user.tpl', $this->page_content);
    }
};
?>
